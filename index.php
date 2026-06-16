<?php
declare(strict_types=1);

date_default_timezone_set('Europe/Moscow');

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function digits_only(string $value): string
{
    return preg_replace('/\D+/', '', $value) ?? '';
}

function phone_href(string $phone): string
{
    $digits = digits_only($phone);

    if (strlen($digits) === 11 && $digits[0] === '8') {
        $digits = '7' . substr($digits, 1);
    }

    return '+' . $digits;
}

function next_working_day(array $hours, DateTimeImmutable $now): array
{
    for ($i = 1; $i <= 7; $i++) {
        $candidate = $now->modify('+' . $i . ' day');
        $day = (int) $candidate->format('N');

        if (!empty($hours[$day]['open'])) {
            return [
                'label' => $hours[$day]['label'],
                'short' => $hours[$day]['short'],
                'open' => $hours[$day]['open'],
                'close' => $hours[$day]['close'],
            ];
        }
    }

    return ['label' => 'Ближайший рабочий день', 'short' => 'день', 'open' => '09:00', 'close' => '19:00'];
}

function open_status(array $hours): array
{
    $zone = new DateTimeZone('Europe/Moscow');
    $now = new DateTimeImmutable('now', $zone);
    $day = (int) $now->format('N');
    $today = $hours[$day];

    if (empty($today['open'])) {
        $next = next_working_day($hours, $now);

        return [
            'state' => 'closed',
            'label' => 'Сегодня выходной',
            'detail' => 'Ближайший прием: ' . $next['label'] . ' с ' . $next['open'] . ' до ' . $next['close'],
        ];
    }

    $open = DateTimeImmutable::createFromFormat('Y-m-d H:i', $now->format('Y-m-d') . ' ' . $today['open'], $zone);
    $close = DateTimeImmutable::createFromFormat('Y-m-d H:i', $now->format('Y-m-d') . ' ' . $today['close'], $zone);

    if ($open !== false && $close !== false && $now < $open) {
        return [
            'state' => 'soon',
            'label' => 'Откроется сегодня в ' . $today['open'],
            'detail' => $today['label'] . ': с ' . $today['open'] . ' до ' . $today['close'],
        ];
    }

    if ($open !== false && $close !== false && $now <= $close) {
        return [
            'state' => 'open',
            'label' => 'Открыто до ' . $today['close'],
            'detail' => $today['label'] . ': с ' . $today['open'] . ' до ' . $today['close'],
        ];
    }

    $next = next_working_day($hours, $now);

    return [
        'state' => 'closed',
        'label' => 'Сегодня уже закрыто',
        'detail' => 'Ближайший прием: ' . $next['label'] . ' с ' . $next['open'] . ' до ' . $next['close'],
    ];
}

function status_schedule_tabs(array $status): array
{
    $detail = $status['detail'] ?? '';

    if (preg_match('/^Ближайший прием:\s*(.+?)\s+с\s+(.+?)\s+до\s+(.+)$/u', $detail, $matches) === 1) {
        return [
            'title' => 'Ближайший прием',
            'day' => $matches[1],
            'timeLabel' => 'время',
            'time' => 'с ' . $matches[2] . ' до ' . $matches[3],
        ];
    }

    if (preg_match('/^([^:]+):\s*с\s*(.+?)\s+до\s+(.+)$/u', $detail, $matches) === 1) {
        return [
            'title' => 'Сегодня',
            'day' => $matches[1],
            'timeLabel' => 'время',
            'time' => 'с ' . $matches[2] . ' до ' . $matches[3],
        ];
    }

    return [
        'title' => 'Прием',
        'day' => 'уточните',
        'timeLabel' => 'по',
        'time' => 'телефону',
    ];
}

$business = [
    'name' => 'Авангард',
    'rating' => '4,9',
    'ratingValue' => 4.9,
    'ratingCount' => 692,
    'reviewCount' => 277,
    'address' => 'Красная ул., 0/8, Электросталь',
    'phones' => ['+7 (926) 353-83-54', '+7 (903) 776-73-44'],
    'mapsUrl' => 'https://yandex.ru/maps/-/CPhtrOk2',
    'description' => 'Автосервис в Электростали: диагностика перед сметой, согласование работ до ремонта, ТО, сход-развал, ходовая, двигатель, тормоза и запчасти под заказ.',
];

$hours = [
    1 => ['label' => 'Понедельник', 'short' => 'пн', 'open' => '09:00', 'close' => '19:00'],
    2 => ['label' => 'Вторник', 'short' => 'вт', 'open' => '09:00', 'close' => '19:00'],
    3 => ['label' => 'Среда', 'short' => 'ср', 'open' => '09:00', 'close' => '19:00'],
    4 => ['label' => 'Четверг', 'short' => 'чт', 'open' => '09:00', 'close' => '19:00'],
    5 => ['label' => 'Пятница', 'short' => 'пт', 'open' => '09:00', 'close' => '19:00'],
    6 => ['label' => 'Суббота', 'short' => 'сб', 'open' => '09:00', 'close' => '17:00'],
    7 => ['label' => 'Воскресенье', 'short' => 'вс', 'open' => null, 'close' => null],
];

$status = open_status($hours);
$statusSchedule = status_schedule_tabs($status);

$serviceGroups = [
    [
        'title' => 'Сход-развал и колеса',
        'summary' => 'Для машины, которую тянет в сторону, после ремонта подвески или перед сезоном.',
        'items' => ['развал-схождение', 'шиномонтаж', 'проверка углов после ремонта', 'быстрый заезд по записи'],
    ],
    [
        'title' => 'ТО и расходники',
        'summary' => 'Плановое обслуживание без лишних работ: масло, фильтры, ремни, ГРМ.',
        'items' => ['замена масла', 'замена фильтров', 'замена ГРМ и ремней', 'раскоксовка', 'промывка инжектора'],
    ],
    [
        'title' => 'Ходовая и рулевое',
        'summary' => 'Когда есть стук, люфт, скрип, биение или машину стало вести нестабильно.',
        'items' => ['ремонт ходовой части', 'шаровые опоры', 'рулевые рейки', 'гидроусилитель руля', 'ступичные узлы'],
    ],
    [
        'title' => 'Двигатель и трансмиссия',
        'summary' => 'Ремонт узлов с предварительным осмотром и согласованием состава работ.',
        'items' => ['замена двигателя', 'эндоскопия двигателя', 'ремонт ГБЦ', 'ремонт КПП', 'замена сцепления'],
    ],
    [
        'title' => 'Тормоза, охлаждение, кондиционер',
        'summary' => 'Системы, где важно быстро найти причину и не менять исправные детали.',
        'items' => ['ремонт тормозной системы', 'ремонт системы охлаждения', 'промывка отопителя', 'заправка кондиционера'],
    ],
    [
        'title' => 'Выхлоп, сварка, оснащение',
        'summary' => 'Работы по выхлопной системе, катализаторам и дополнительному оборудованию.',
        'items' => ['ремонт выхлопной системы', 'удаление катализаторов', 'сварочные работы', 'установка фаркопа', 'ремонт коммерческого транспорта'],
    ],
];

$quickServices = [
    'Сход-развал',
    'Замена масла и фильтров',
    'Диагностика ходовой',
    'Ремонт тормозов',
    'Замена ГРМ или ремней',
    'Чистка форсунок',
    'Замена сцепления',
    'Замена двигателя',
    'Заправка кондиционера',
    'Выхлопная система',
    'Сварочные работы',
    'Фаркоп',
];

$trustItems = [
    [
        'title' => 'Стоимость до начала работ',
        'text' => 'В отзывах клиенты отдельно отмечают, что администратор понятно объясняет цену работ и запчастей.',
    ],
    [
        'title' => 'Запись или быстрый заезд',
        'text' => 'Есть предварительная запись. По отзывам, при свободном окне иногда принимают сразу.',
    ],
    [
        'title' => 'Запчасти под заказ',
        'text' => 'Можно не бегать по магазинам: в карточке указаны запчасти и комплектующие под заказ.',
    ],
    [
        'title' => 'Гарантия и оплата после работ',
        'text' => 'В особенностях указаны гарантия, постоплата, карты, QR, СБП, наличные и безналичный расчет.',
    ],
];

$reviews = [
    [
        'name' => 'михаил мандровский',
        'date' => '9 февраля',
        'text' => 'Администратор все объясняет понятно и доходчиво, сколько стоит та или иная работа.',
    ],
    [
        'name' => 'Иван Головко',
        'date' => '4 февраля',
        'text' => 'Без лишних услуг, честные цены, мастера всегда подскажут и покажут.',
    ],
    [
        'name' => 'Олеся Савельева',
        'date' => '26 июня 2025',
        'text' => 'Честно все рассказали, все объяснили, все сделали в срок.',
    ],
    [
        'name' => 'Роман Мухин',
        'date' => '17 апреля',
        'text' => 'Большой сервис, много подъемников, практически всегда можно попасть на ремонт сразу.',
    ],
    [
        'name' => 'Николай Коньшин',
        'date' => '14 марта 2025',
        'text' => 'При мне определили неисправность, заказали деталь, поставили. Главное - не разводили на доп. работы.',
    ],
    [
        'name' => 'ЮЛЯ ЕМЕЛИНА',
        'date' => '5 сентября 2023',
        'text' => 'Не надо бегать по магазинам искать запчасти: согласовали цену за детали и можно спокойно ждать.',
    ],
];

$amenities = [
    'парковка',
    'Wi-Fi',
    'туалет',
    'можно с животными',
    'пандус',
    'доступный вход',
    'парковка для людей с инвалидностью',
    'оплата картой, QR, СБП, наличными и безналом',
];

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$path = strtok($_SERVER['REQUEST_URI'] ?? '/', '?') ?: '/';
$canonical = $scheme . '://' . $host . $path;

$openingHoursSpecification = [];
foreach ($hours as $day) {
    if (empty($day['open'])) {
        continue;
    }

    $openingHoursSpecification[] = [
        '@type' => 'OpeningHoursSpecification',
        'dayOfWeek' => 'https://schema.org/' . [
            'Понедельник' => 'Monday',
            'Вторник' => 'Tuesday',
            'Среда' => 'Wednesday',
            'Четверг' => 'Thursday',
            'Пятница' => 'Friday',
            'Суббота' => 'Saturday',
        ][$day['label']],
        'opens' => $day['open'],
        'closes' => $day['close'],
    ];
}

$schema = [
    '@context' => 'https://schema.org',
    '@type' => 'AutoRepair',
    'name' => $business['name'],
    'description' => $business['description'],
    'url' => $canonical,
    'telephone' => array_map('phone_href', $business['phones']),
    'address' => [
        '@type' => 'PostalAddress',
        'streetAddress' => 'Красная ул., 0/8',
        'addressLocality' => 'Электросталь',
        'addressCountry' => 'RU',
    ],
    'aggregateRating' => [
        '@type' => 'AggregateRating',
        'ratingValue' => $business['ratingValue'],
        'ratingCount' => $business['ratingCount'],
        'reviewCount' => $business['reviewCount'],
        'bestRating' => 5,
    ],
    'openingHoursSpecification' => $openingHoursSpecification,
    'sameAs' => [$business['mapsUrl']],
    'paymentAccepted' => ['Cash', 'Credit Card', 'Debit Card', 'QR', 'SBP', 'Bank transfer'],
    'hasOfferCatalog' => [
        '@type' => 'OfferCatalog',
        'name' => 'Услуги автосервиса',
        'itemListElement' => array_map(static function (array $group): array {
            return [
                '@type' => 'OfferCatalog',
                'name' => $group['title'],
                'itemListElement' => array_map(static fn (string $item): array => [
                    '@type' => 'Offer',
                    'itemOffered' => [
                        '@type' => 'Service',
                        'name' => $item,
                    ],
                ], $group['items']),
            ];
        }, $serviceGroups),
    ],
    'review' => array_map(static fn (array $review): array => [
        '@type' => 'Review',
        'author' => [
            '@type' => 'Person',
            'name' => $review['name'],
        ],
        'reviewBody' => $review['text'],
    ], array_slice($reviews, 0, 4)),
];

?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Автосервис Авангард в Электростали - ремонт, ТО, сход-развал</title>
    <meta name="description" content="Автосервис Авангард в Электростали: сначала диагностика и понятная смета, потом ремонт. Звонок для записи, уточнения свободного окна и запчастей.">
    <meta name="keywords" content="Авангард автосервис Электросталь, сход-развал Электросталь, ремонт авто Электросталь, замена масла, ремонт ходовой, шиномонтаж">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?= e($canonical); ?>">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Автосервис Авангард в Электростали">
    <meta property="og:description" content="Ремонт авто в Электростали: диагностика перед сметой, согласование работ и запись по телефону. Рейтинг 4,9 по 692 оценкам.">
    <meta property="og:url" content="<?= e($canonical); ?>">
    <meta property="og:locale" content="ru_RU">
    <meta name="theme-color" content="#0b6e99">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css?v=liquid-glass-12">
    <script type="application/ld+json"><?= json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT); ?></script>
</head>
<body>
    <a class="skip-link" href="#main">Перейти к содержанию</a>

    <header class="site-header" data-site-header>
        <div class="container header-grid">
            <a class="brand" href="#top" aria-label="Авангард - к началу страницы">
                <span class="brand-mark" aria-hidden="true">
                    <svg viewBox="0 0 32 32" focusable="false">
                        <path d="M6 21h20l-3.5-8.5A4 4 0 0 0 18.8 10h-5.6a4 4 0 0 0-3.7 2.5L6 21Z"></path>
                        <path d="M9 21v3m14-3v3M11 16h10"></path>
                    </svg>
                </span>
                <span>
                    <strong>Авангард</strong>
                    <small>автосервис</small>
                </span>
            </a>

            <nav class="main-nav" aria-label="Основная навигация">
                <a href="#services">Услуги</a>
                <a href="#process">Как работаем</a>
                <a href="#reviews">Отзывы</a>
                <a href="#contacts">Контакты</a>
            </nav>

            <div class="header-actions">
                <span class="status-pill status-<?= e($status['state']); ?>">
                    <span aria-hidden="true"></span>
                    <?= e($status['label']); ?>
                </span>
                <a class="link-call" href="tel:<?= e(phone_href($business['phones'][0])); ?>">
                    <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M6.6 10.8c1.4 2.8 3.8 5.2 6.6 6.6l2.2-2.2c.3-.3.8-.4 1.2-.3 1.3.4 2.6.6 4 .6.7 0 1.2.5 1.2 1.2v3.5c0 .7-.5 1.2-1.2 1.2C10.6 22 2 13.4 2 2.8c0-.7.5-1.2 1.2-1.2h3.5c.7 0 1.2.5 1.2 1.2 0 1.4.2 2.7.6 4 .1.4 0 .8-.3 1.2l-1.6 2.8Z"></path></svg>
                    <span class="link-call-number"><?= e($business['phones'][0]); ?></span>
                    <span class="link-call-label">Позвонить</span>
                </a>
            </div>
        </div>
    </header>

    <main id="main">
        <section class="hero" id="top">
            <img class="hero-bg" src="assets/img/avangard-hero-industrial.png" alt="" aria-hidden="true" loading="eager">
            <div class="container hero-grid">
                <div class="hero-copy">
                    <p class="eyebrow">Автосервис в Электростали</p>
                    <h1>Ремонт авто в Электростали без лишних работ</h1>
                    <p class="lead">Сначала разбираемся в причине, затем согласуем смету и детали. Позвоните, чтобы понять ближайший шаг до приезда в сервис.</p>

                    <div class="hero-actions" aria-label="Основные действия">
                        <a class="btn btn-primary" href="tel:<?= e(phone_href($business['phones'][0])); ?>">
                            <span class="btn-icon-soft" aria-hidden="true">
                                <svg class="icon" viewBox="0 0 24 24"><path d="M6.6 10.8c1.4 2.8 3.8 5.2 6.6 6.6l2.2-2.2c.3-.3.8-.4 1.2-.3 1.3.4 2.6.6 4 .6.7 0 1.2.5 1.2 1.2v3.5c0 .7-.5 1.2-1.2 1.2C10.6 22 2 13.4 2 2.8c0-.7.5-1.2 1.2-1.2h3.5c.7 0 1.2.5 1.2 1.2 0 1.4.2 2.7.6 4 .1.4 0 .8-.3 1.2l-1.6 2.8Z"></path></svg>
                            </span>
                            <span>Позвонить и записаться</span>
                        </a>
                        <a class="btn btn-secondary" href="#call">
                            <span class="btn-icon-soft" aria-hidden="true">
                                <svg class="icon" viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"></path></svg>
                            </span>
                            <span>Все телефоны</span>
                        </a>
                    </div>

                    <dl class="proof-grid" aria-label="Ключевые факты">
                        <div>
                            <dt><?= e($business['rating']); ?></dt>
                            <dd>рейтинг на Яндекс.Картах</dd>
                        </div>
                        <div>
                            <dt><?= e((string) $business['ratingCount']); ?></dt>
                            <dd>оценки клиентов</dd>
                        </div>
                        <div>
                            <dt><?= e((string) $business['reviewCount']); ?></dt>
                            <dd>отзывов в карточке</dd>
                        </div>
                    </dl>
                </div>

                <aside class="hero-panel" aria-label="Быстрая информация для визита">
                    <div class="panel-heading">
                        <span class="status-pill status-<?= e($status['state']); ?>">
                            <span aria-hidden="true"></span>
                            <?= e($status['label']); ?>
                        </span>
                        <p><?= e($status['detail']); ?></p>
                    </div>

                    <div class="bay-board" aria-label="Порядок обращения">
                        <div class="bay-row">
                            <span>1</span>
                            <strong>Звонок</strong>
                            <small>подбирают окно</small>
                        </div>
                        <div class="bay-row">
                            <span>2</span>
                            <strong>Осмотр</strong>
                            <small>понятна причина</small>
                        </div>
                        <div class="bay-row">
                            <span>3</span>
                            <strong>Смета</strong>
                            <small>цена до ремонта</small>
                        </div>
                        <div class="bay-row">
                            <span>4</span>
                            <strong>Ремонт</strong>
                            <small>оплата после работ</small>
                        </div>
                    </div>

                    <div class="contact-stack">
                        <?php foreach ($business['phones'] as $phone): ?>
                            <a href="tel:<?= e(phone_href($phone)); ?>"><?= e($phone); ?></a>
                        <?php endforeach; ?>
                    </div>

                    <a class="map-link" href="<?= e($business['mapsUrl']); ?>" target="_blank" rel="noopener">
                        <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s7-5.4 7-12A7 7 0 1 0 5 9c0 6.6 7 12 7 12Z"></path><path d="M12 12a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"></path></svg>
                        Открыть маршрут в Яндекс.Картах
                    </a>
                </aside>
            </div>
        </section>

        <section class="section pain-section">
            <div class="container">
                <div class="section-heading narrow">
                    <p class="eyebrow">С чего начать</p>
                    <h2>Опишите симптом - подскажем, куда копать</h2>
                    <p>Стук, перегрев, вибрация, тяга в сторону или плановое ТО: выберите похожую задачу и используйте ее как подсказку для звонка.</p>
                </div>

                <div class="quick-grid" aria-label="Популярные услуги">
                    <?php foreach ($quickServices as $service): ?>
                        <button class="quick-chip" type="button" data-service="<?= e($service); ?>">
                            <?= e($service); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="section" id="services">
            <div class="container">
                <div class="section-heading split">
                    <div>
                        <p class="eyebrow">Что ремонтируем</p>
                        <h2>Показываем работы по понятным проблемам, не по прайсу</h2>
                    </div>
                    <p>Вы видите не сухой список услуг, а ситуации, с которыми обычно приезжают: ходовая, ТО, двигатель, тормоза, охлаждение, выхлоп.</p>
                </div>

                <div class="services-grid">
                    <?php foreach ($serviceGroups as $index => $group): ?>
                        <article class="service-card">
                            <span class="card-index"><?= e(str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)); ?></span>
                            <h3><?= e($group['title']); ?></h3>
                            <p><?= e($group['summary']); ?></p>
                            <ul>
                                <?php foreach ($group['items'] as $item): ?>
                                    <li><?= e($item); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="section contrast-section" id="process">
            <div class="container">
                <div class="section-heading split light">
                    <div>
                        <p class="eyebrow">Как снижаем риски</p>
                        <h2>Не начинаем ремонт, пока не понятны причина и смета</h2>
                    </div>
                    <p>На осмотре уточняют, что действительно нужно делать, какие детали понадобятся и сколько это будет стоить до начала работ.</p>
                </div>

                <figure class="process-visual" aria-label="Техническая диагностика и сход-развал">
                    <img src="assets/img/avangard-alignment-process.png" alt="Сход-развал и диагностика колеса в автосервисе" loading="lazy">
                    <figcaption>
                        <strong>Диагностика до ремонта</strong>
                        <span>Сначала причина и состав работ, затем согласование цены и деталей.</span>
                    </figcaption>
                </figure>

                <div class="section-heading narrow light trust-heading">
                    <p class="eyebrow">Почему спокойнее</p>
                    <h2>Вы понимаете, за что платите, до начала ремонта</h2>
                    <p>Стоимость, детали, сроки и порядок работ обсуждаются заранее. Если нужны запчасти, их можно согласовать до ремонта.</p>
                </div>

                <p class="process-group-label">Что согласуем до старта</p>
                <div class="trust-grid">
                    <?php foreach ($trustItems as $item): ?>
                        <article class="trust-card">
                            <h3><?= e($item['title']); ?></h3>
                            <p><?= e($item['text']); ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>

                <p class="process-group-label process-group-label-timeline">Как проходит визит</p>
                <ol class="timeline" aria-label="Порядок ремонта">
                    <li>
                        <strong>Описываете задачу</strong>
                        <span>Марка, симптом, срочность, свои запчасти или нужна помощь с подбором.</span>
                    </li>
                    <li>
                        <strong>Получаете окно визита</strong>
                        <span>Можно записаться заранее. При свободных постах администратор подскажет быстрый заезд.</span>
                    </li>
                    <li>
                        <strong>Согласуете работы</strong>
                        <span>После осмотра понятны стоимость, сроки и какие детали нужны.</span>
                    </li>
                    <li>
                        <strong>Забираете авто</strong>
                        <span>Оплата после работ, рекомендации на будущее и гарантийные вопросы уточняются при выдаче.</span>
                    </li>
                </ol>
            </div>
        </section>

        <section class="section" id="reviews">
            <div class="container">
                <div class="section-heading split">
                    <div>
                        <p class="eyebrow">Что говорят клиенты</p>
                        <h2>В отзывах чаще всего ценят честные объяснения</h2>
                        <p>Люди отмечают понятные цены, отсутствие лишних услуг, помощь с деталями и готовность показать, что именно сломалось.</p>
                    </div>
                    <div class="rating-summary" aria-label="Рейтинг">
                        <strong><?= e($business['rating']); ?> из 5</strong>
                        <span><?= e((string) $business['ratingCount']); ?> оценок, <?= e((string) $business['reviewCount']); ?> отзывов</span>
                    </div>
                </div>

                <div class="review-grid">
                    <?php foreach ($reviews as $review): ?>
                        <article class="review-card">
                            <div class="review-top">
                                <strong><?= e($review['name']); ?></strong>
                                <span><?= e($review['date']); ?></span>
                            </div>
                            <p>«<?= e($review['text']); ?>»</p>
                        </article>
                    <?php endforeach; ?>
                </div>

                <div class="note-box">
                    <strong>Согласуйте не только время, но и порядок проверки</strong>
                    <p>Назовите симптом, марку авто, срочность и вопрос по деталям. Так меньше риска ждать лишнее или обсуждать стоимость уже после начала работ.</p>
                    <a href="<?= e($business['mapsUrl']); ?>" target="_blank" rel="noopener">Смотреть карточку на Яндекс.Картах</a>
                </div>
            </div>
        </section>

        <section class="section amenities-section">
            <div class="container amenities-grid">
                <div class="section-heading">
                    <p class="eyebrow">Перед поездкой</p>
                    <h2>Уточните детали заранее, чтобы не ехать зря</h2>
                    <p>По телефону лучше проверить свободное окно, наличие деталей, тип ремонта и способы оплаты.</p>
                </div>

                <div class="amenities-list" aria-label="Особенности сервиса">
                    <?php foreach ($amenities as $amenity): ?>
                        <span><?= e($amenity); ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="section call-section" id="call">
            <div class="container call-grid">
                <div class="call-copy">
                    <p class="eyebrow">Быстрая связь</p>
                    <h2>Позвоните - скажем, с чего начать именно вам</h2>
                    <p>Не нужно заполнять форму и ждать ответа. По телефону можно сразу обсудить симптом, окно визита, детали и порядок согласования цены.</p>

                    <div class="callout">
                        <strong>Что сказать администратору</strong>
                        <span data-service-hint>Марку авто, симптом, когда удобно приехать, есть ли свои запчасти и нужен ли сход-развал после ремонта.</span>
                    </div>
                </div>

                <div class="phone-panel" aria-label="Телефоны для записи на ремонт">
                    <div class="phone-panel-top">
                        <span class="status-pill status-<?= e($status['state']); ?>">
                            <span aria-hidden="true"></span>
                            <?= e($status['label']); ?>
                        </span>
                        <div class="schedule-tabs" aria-label="<?= e($statusSchedule['title']); ?>">
                            <span class="schedule-tab schedule-tab-title">
                                <small>статус</small>
                                <strong><?= e($statusSchedule['title']); ?></strong>
                            </span>
                            <span class="schedule-tab">
                                <small>день</small>
                                <strong><?= e($statusSchedule['day']); ?></strong>
                            </span>
                            <span class="schedule-tab">
                                <small><?= e($statusSchedule['timeLabel']); ?></small>
                                <strong><?= e($statusSchedule['time']); ?></strong>
                            </span>
                        </div>
                    </div>

                    <div class="phone-card-list">
                        <?php foreach ($business['phones'] as $index => $phone): ?>
                            <a class="phone-card <?= $index === 0 ? 'phone-card-primary' : 'phone-card-secondary'; ?>" href="tel:<?= e(phone_href($phone)); ?>" aria-label="Позвонить в автосервис Авангард по номеру <?= e($phone); ?>"<?= $index === 0 ? ' data-primary-call="true"' : ''; ?>>
                                <span class="phone-icon" aria-hidden="true">
                                    <svg class="icon" viewBox="0 0 24 24"><path d="M6.6 10.8c1.4 2.8 3.8 5.2 6.6 6.6l2.2-2.2c.3-.3.8-.4 1.2-.3 1.3.4 2.6.6 4 .6.7 0 1.2.5 1.2 1.2v3.5c0 .7-.5 1.2-1.2 1.2C10.6 22 2 13.4 2 2.8c0-.7.5-1.2 1.2-1.2h3.5c.7 0 1.2.5 1.2 1.2 0 1.4.2 2.7.6 4 .1.4 0 .8-.3 1.2l-1.6 2.8Z"></path></svg>
                                </span>
                                <strong><?= e($phone); ?></strong>
                            </a>
                        <?php endforeach; ?>
                    </div>

                </div>
            </div>
        </section>

        <section class="section contacts-section" id="contacts">
            <div class="container contacts-grid">
                <div>
                    <p class="eyebrow">Где находимся</p>
                    <h2>Телефоны и маршрут - без лишнего поиска</h2>
                    <p class="contacts-note">Позвоните перед визитом, чтобы уточнить свободное окно, затем откройте маршрут до сервиса на Красной улице.</p>
                    <address><?= e($business['address']); ?></address>
                    <div class="contact-buttons">
                        <?php foreach ($business['phones'] as $phone): ?>
                            <a class="btn btn-secondary" href="tel:<?= e(phone_href($phone)); ?>">
                                <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M6.6 10.8c1.4 2.8 3.8 5.2 6.6 6.6l2.2-2.2c.3-.3.8-.4 1.2-.3 1.3.4 2.6.6 4 .6.7 0 1.2.5 1.2 1.2v3.5c0 .7-.5 1.2-1.2 1.2C10.6 22 2 13.4 2 2.8c0-.7.5-1.2 1.2-1.2h3.5c.7 0 1.2.5 1.2 1.2 0 1.4.2 2.7.6 4 .1.4 0 .8-.3 1.2l-1.6 2.8Z"></path></svg>
                                <?= e($phone); ?>
                            </a>
                        <?php endforeach; ?>
                        <a class="btn btn-map" href="<?= e($business['mapsUrl']); ?>" target="_blank" rel="noopener">
                            <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s7-5.4 7-12A7 7 0 1 0 5 9c0 6.6 7 12 7 12Z"></path><path d="M12 12a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"></path></svg>
                            Маршрут
                        </a>
                    </div>
                </div>

                <div class="hours-card">
                    <h3>Режим работы</h3>
                    <dl>
                        <?php foreach ($hours as $day): ?>
                            <div>
                                <dt><?= e($day['label']); ?></dt>
                                <dd><?= $day['open'] ? e($day['open'] . '-' . $day['close']) : 'Выходной'; ?></dd>
                            </div>
                        <?php endforeach; ?>
                    </dl>
                </div>
            </div>
        </section>
    </main>

    <footer class="site-footer" id="documents">
        <div class="container footer-shell">
            <div class="footer-brand-panel">
                <a class="footer-brand" href="#top" aria-label="Авангард - к началу страницы">
                    <span class="brand-mark" aria-hidden="true">
                        <svg viewBox="0 0 32 32" focusable="false">
                            <path d="M6 21h20l-3.5-8.5A4 4 0 0 0 18.8 10h-5.6a4 4 0 0 0-3.7 2.5L6 21Z"></path>
                            <path d="M9 21v3m14-3v3M11 16h10"></path>
                        </svg>
                    </span>
                    <span>
                        <strong><?= e($business['name']); ?></strong>
                        <small>Автосервис в Электростали</small>
                    </span>
                </a>
                <p>Запись и срочные вопросы - по телефону. На сайте нет формы заявки, онлайн-оплаты и личного кабинета.</p>
                <div class="footer-call-row" aria-label="Телефоны автосервиса">
                    <?php foreach ($business['phones'] as $phone): ?>
                        <a href="tel:<?= e(phone_href($phone)); ?>"><?= e($phone); ?></a>
                    <?php endforeach; ?>
                </div>
            </div>

            <nav class="footer-column" aria-label="Разделы сайта">
                <h2>Разделы</h2>
                <a href="#services">Услуги</a>
                <a href="#process">Как работаем</a>
                <a href="#reviews">Отзывы</a>
                <a href="#call">Позвонить</a>
                <a href="#contacts">Контакты</a>
            </nav>

            <div class="footer-column">
                <h2>Контакты</h2>
                <address><?= e($business['address']); ?></address>
                <span>Пн-пт: 09:00-19:00</span>
                <span>Сб: 09:00-17:00</span>
                <span>Вс: выходной</span>
                <a href="<?= e($business['mapsUrl']); ?>" target="_blank" rel="noopener">Маршрут в Яндекс.Картах</a>
            </div>

            <nav class="footer-column footer-docs" aria-label="Документы сайта">
                <h2>Документы</h2>
                <a href="privacy.html">Политика обработки персональных данных</a>
                <a href="terms.html">Условия использования сайта</a>
                <p>Документы учитывают текущий функционал: сайт информирует об услугах и переводит пользователя на звонок.</p>
            </nav>
        </div>

        <div class="container footer-bottom">
            <p>© <?= date('Y'); ?> <?= e($business['name']); ?>. Информация на сайте не является публичной офертой.</p>
            <a href="#top">Наверх</a>
        </div>
    </footer>

    <div class="mobile-cta" aria-label="Быстрые действия">
        <a href="tel:<?= e(phone_href($business['phones'][0])); ?>">
            <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M6.6 10.8c1.4 2.8 3.8 5.2 6.6 6.6l2.2-2.2c.3-.3.8-.4 1.2-.3 1.3.4 2.6.6 4 .6.7 0 1.2.5 1.2 1.2v3.5c0 .7-.5 1.2-1.2 1.2C10.6 22 2 13.4 2 2.8c0-.7.5-1.2 1.2-1.2h3.5c.7 0 1.2.5 1.2 1.2 0 1.4.2 2.7.6 4 .1.4 0 .8-.3 1.2l-1.6 2.8Z"></path></svg>
            Позвонить 926
        </a>
        <a href="tel:<?= e(phone_href($business['phones'][1])); ?>">Позвонить 903</a>
        <a href="<?= e($business['mapsUrl']); ?>" target="_blank" rel="noopener">Маршрут</a>
    </div>

    <script src="assets/js/main.js?v=liquid-glass-5" defer></script>
</body>
</html>

