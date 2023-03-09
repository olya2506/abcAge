<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Web page</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-GLhlTQ8iRABdZLl6O3oVMWSktQOp6b7In1Zl3/Jr59b6EGGoI1aFkw7cmDA6j6gD" crossorigin="anonymous">
</head>

<body class="position-absolute top-50 start-50 translate-middle">
    <div class="input-group text-center">
        <form class="form-group" style="width: 220px">
            <input type="date" name="date" class="form-control"><br>
            <span id="invalid" class="text-danger"></span>
            <button type="submit" class="btn btn-dark">Применить</button><br><br>
            <span id="info" class="form-control" style="height: 65px"></span>
        </form>
    </div>
</body>

<script>
    <?php require "js/script.js" ?>
</script>

</html>
