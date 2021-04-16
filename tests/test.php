<?php
$store_lazadas =
    [
        11,
        247
    ];
$ebay_store_ids =
    [
        13,
        28,
        69,
        95,
        112,
        118,
        159,
        200,
        248,
        252];
$common_store_ids = [
    18,
    32,
    33,
    34,
    35,
    75,
    115,
    140,
    147,
    180,
];
$cd_store_ids = [
    256,
    239,
    222,
    83,
    27,
    26,
];
$qoo10_store_ids = [
    225
];
$dates = [
//    ['startDate' => '20200620000000', 'endDate' => '20200620235959'],
//    ['startDate' => '20200621000000', 'endDate' => '20200621235959'],
    ['startDate' => '20200802080000', 'endDate' => '20200802100000'],
    ['startDate' => '20200802100000', 'endDate' => '20200802120000'],

];
foreach ($qoo10_store_ids as $id) {
    foreach ($dates as $date) {
        $url = "http://general.b5cai.com/op/crawler?stores={$id}&startDate={$date['startDate']}&endDate={$date['endDate']}";
//        $res = file_get_contents($url);
        echo $url . PHP_EOL;
//        echo $res . PHP_EOL;
    }
    echo '<br/>';
}