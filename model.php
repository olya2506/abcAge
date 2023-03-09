<?php

require_once 'conf/db.php';

/**
 * Записывает в БД поставки на склад и отправки со склада
 * для каждого дня с начала ведения БД по сегодняшний день
 *
 * @param string $today Дата из инпут-поля
 * @param string $product Товар для формирования отправок
 * @return void
 */
function insert_data(string $today, string $product): void
{
    // задаем дату начала ведения базы
    $current_date = '2021-01-01';

    while ($current_date <= $today) {
        $receive = create_received($current_date);
        if ($receive)
            execute($receive);

        $dispatch = create_dispatch($current_date, $product);
        if ($dispatch)
            execute($dispatch);

        $current_date = date('Y-m-d', strtotime($current_date . '+1 Day'));
    }
}


/**
 * Создает SQL-запрос для записи в БД поставки на склад на текущую дату
 *
 * @param string $date Текущая дата
 * @return string SQL-запрос
 */
function create_received(string $date): string
{
    $received = [
        ["id" => "1", "product" => "Колбаса", "received_quantity" => 300, "cost" => 5000, "receive_date" => "2021-01-01"],
        ["id" => "t-500", "product" => "Пармезан", "received_quantity" => 10, "cost" => 6000, "receive_date" => "2021-01-02"],
        ["id" => "12-TP-777", "product" => "Левый носок", "received_quantity" => 100, "cost" => 500, "receive_date" => "2021-01-13"],
        ["id" => "12-TP-778", "product" => "Левый носок", "received_quantity" => 50, "cost" => 300, "receive_date" => "2021-01-14"],
        ["id" => "12-TP-779", "product" => "Левый носок", "received_quantity" => 77, "cost" => 539, "receive_date" => "2021-01-20"],
        ["id" => "12-TP-877", "product" => "Левый носок", "received_quantity" => 32, "cost" => 176, "receive_date" => "2021-01-30"],
        ["id" => "12-TP-977", "product" => "Левый носок", "received_quantity" => 94, "cost" => 554, "receive_date" => "2021-02-01"],
        ["id" => "12-TP-979", "product" => "Левый носок", "received_quantity" => 200, "cost" => 1000, "receive_date" => "2021-02-05"]
    ];

    $query = '';

    foreach ($received as $value) {
        if ($value['receive_date'] == $date) {
            $query = 'INSERT INTO `received` (`id`, `product`, `received_quantity`, `cost`, `receive_date`)
                        VALUES (
                                "' . $value['id'] . '", 
                                "' . $value['product'] . '", 
                                ' . $value['received_quantity'] . ', 
                                ' . $value['cost'] . ', 
                                "' . $value['receive_date'] . '"
                                )';
        }
    }
    return $query;
}


/**
 * Создает SQL-запрос для записи в БД отправки со склада в магазин на текущую дату
 *
 * @param string $date Текущая дата
 * @param string $product Товар для формирования отправки
 * @return string SQL-запрос
 */
function create_dispatch(string $date, string $product): string
{
    $query = '';

    // Дата начала формирования предзаказов
    $start_dispatch = '2021-01-13';

    if ($date >= $start_dispatch) {
        $preorder = def_preorder($date, $start_dispatch);
        $dispatch_quantity = def_dispatch($preorder, $product);
        $unit_price = calc_price($product);

        $query = 'INSERT INTO `dispatch` (`product`, `preorder`, `dispatch_quantity`, `unit_price`, `dispatch_date`)
                VALUES (
                        "'.$product.'",
                        '.$preorder.',
                        '.$dispatch_quantity.',
                        '.$unit_price.',
                        "'.$date.'"
                        )';
    }
    return $query;
}


/**
 * Считает кол-во дней со дня начала предзаказов по сегодняшний день
 * и возвращает кол-во предзаказов на текущую дату
 *
 * Если есть невыполненные предзаказы с прошлых дней,
 * добавляет их к сегодняшнему предзаказу
 *
 * @param string $date1 Текущая дата
 * @param string $date2 Дата начала формирования предзаказов
 * @return int Количество предзаказов
 */
function def_preorder(string $date1, string $date2) {
    $date1 = new DateTime($date1);
    $date2 = new DateTime($date2);

    // N-ый элемент последовательности Фибоначчи
    $n = $date1->diff($date2)->format("%a");

    // последовательность будет начинаться с 1, 2..
    $first = 0;
    $second = 1;

    for ($i = 0; $i <= $n; $i++) {
        $sum = $first + $second;
        $first = $second;
        $second = $sum;
    }

    // если по прошлому предзаказу было недостаточно товаров на складе, то он добавляется к следующему предзаказу
    $unsolved = execute('SELECT `preorder` - `dispatch_quantity` 
                        FROM `dispatch` 
                        ORDER BY `dispatch_date` DESC
                        ');
    if ($unsolved)
        return $second + $unsolved['`preorder` - `dispatch_quantity`'];
    else
        return $second;
}


/**
 * Проверяет, кол-во товара на складе
 * Если недостаточно для предзаказа, отправляется сколько есть
 * Если на складе 0, товаров к отправке - 0
 *
 * @param string $preorder Кол-во прездзаказов
 * @param string $product Предзаказанный товар
 * @return int Кол-во товаров к отправке
 */
function def_dispatch(string $preorder, string $product): int
{
    $balance = execute('SELECT `balance` 
                                FROM `warehouse` 
                                WHERE `product` = "' . $product . '"
                                ')['balance'];

    return min($preorder, $balance);
}


/**
 * Считает цену за единицу товара
 * Цена = общая стоимость последних 5 поставок,
 * разделенная на суммарное кол-во товара в этих поставках, с 30% наценкой
 *
 * @param string $product Товар для расчета цены
 * @return float Цена за единицу товара
 */
function calc_price(string $product): float
{
    $prices = execute('SELECT SUM(`cost`) / SUM(`received_quantity`)
                                FROM (
                                    SELECT `received_quantity`, `cost` 
                                    FROM `received`
                                    WHERE `product` = "' . $product . '"
                                    ORDER BY `receive_date` DESC 
                                    LIMIT 5
                                    ) AS T
                                ')['SUM(`cost`) / SUM(`received_quantity`)'];

    return round($prices * 1.3, 2);
}


/**
 * Запрашивает в БД цену и остаток товара на складе
 * Или выводит информационное сообщение
 *
 * @param string $date Сегодняшняя дата
 * @param string $product Товар
 * @return array Данные из базы или информационное сообщение
 */
function select_data(string $date, string $product): array
{
    $balance = execute('SELECT `balance`
                                FROM `warehouse`
                                WHERE `product` = "' . $product . '"');

    $data = execute('SELECT `unit_price`, `dispatch_quantity`
                                FROM `dispatch`
                                WHERE `dispatch_date` = "' . $date . '"');

    if (!$data)
        return ['info' => 'Нет предзаказов на сегодня'];

    if ($data['dispatch_quantity'] == 0)
        return ['info' => 'Недостаточно товара на складе'];

    return ["unit_price" => $data['unit_price'], "balance" => $balance['balance']];
}
