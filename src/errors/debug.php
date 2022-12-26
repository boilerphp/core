{{ $ex = ~ex~ }}

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <table>
        <thead>
            <tr>
                <th colspan="5">
                    <h1>Exception: @{{ $ex->getMessage(). ' ' . $ex->getLine() }}</h1>
                    <p>File: @{{ $ex->getFile() }}</p>
                </th>
            </tr>
        </thead>
        <tbody>
            @foreach($ex->getTrace() as $trace)
                <tr>
                    <td colspan="2">@{{ $trace["file"]}}</td>
                    <td colspan="3">@{{ $trace["message"] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>