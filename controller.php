<?php

/**
 * Обрабатывает запрос от клиента и возвращает ответ
 *
 * @return array
 */
function process(): array
{
    // расшифровка данных из тела post-запроса
    $date = json_decode(file_get_contents('php://input'), true)['date'];

    // товар мог бы тоже выбираться в инпут-поле на странице и приходить в post-запросе
    $product = 'Левый носок';

    if (!$date)
        return ['invalid' => 'Введите дату'];

    elseif (!DateTime::createFromFormat('Y-m-d', $date))
        return ['invalid' => 'Неверный формат даты'];

    else {
        require_once 'model.php';

        // эмуляция существующей БД склада на сегодняшний день
        insert_data($date, $product);

        // запрос нужной информации в БД
        $data = select_data($date, $product);

        truncate();

        return $data;
    }
}

// ответ сервера в формате json
echo json_encode(process());