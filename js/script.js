const form = document.querySelector('form');

// элемент для вывода сообщений с сервера
const text = document.createElement('div');


form.addEventListener('submit', function(event){
    // отменить перезагрузку страницы при сабмите
    event.preventDefault();

    text.innerHTML = '';

    // дата, введенная в форме
    const date = {date: form.date.value};
    postData(date);
});


/**
 * Делает аякс-запрос на сервер
 */
async function postData(date){
    const response = await fetch('/controller.php',{
        method: 'POST',
        headers: {'Content-Type': 'application/json;charset=utf-8'},
        body: JSON.stringify(date)
    });
    const result = await response.json();
    showResult(result);
}


/**
 * Выводит информационное сообщение или результат в секцию
 */
function showResult(result) {
    if (result['invalid']) {
        text.innerHTML = result['invalid'] + '<br><br>';
        let invalid = document.getElementById('invalid');
        invalid.append(text);
    }
    else {
        if (result['info']) {
            text.innerHTML = result['info'];
        }
        else {
            text.innerHTML = 'Цена товара: ' + result['unit_price'] + '<br>' + 'Остаток на складе: ' + result['balance'];
        }
        let info = document.getElementById('info');
        info.append(text);
    }
}