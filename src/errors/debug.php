{{ $ex = ~ex~ }}

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title></title>
</head>

<body>
    <table style="border: 1px solid #dcdcdc;">
        <thead style="border: 1px solid #dcdcdc;">
            <tr style="border: 1px solid #dcdcdc;">
                <th colspan="5" align="left">
                    <h3>Exception: @{{ $ex->getMessage() }}</h3>
                </th>
            </tr>
        </thead>
        <tbody style="border: 1px solid #dcdcdc;">
            @foreach($ex->getTrace() as $trace)
            <tr style="border: 1px solid #dcdcdc;">
                <td colspan="5" style="border: 1px solid #dcdcdc;">
                    <p style="margin: 2px;">@{{ $trace["file"]}}</p>
                    <span>@{{ isset($trace["message"]) ? $trace["message"] : '' }}</span>
                    <span>Line @{{ $trace["line"] }}</span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>