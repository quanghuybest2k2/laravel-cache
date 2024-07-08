<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>

<body>
    <h1>Caching in Laravel</h1>
    @foreach ($users as $item)
        <p>{{ $item->name }}</p>
    @endforeach
</body>

</html>
