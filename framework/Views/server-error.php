<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Oops! <?= $title ?? "Error!" ?></title>
    <style>
        body {
            font-family: Roboto, sans-serif;
            background-color: #f5f5f5;
            text-align: center;
            letter-spacing: .5px;
            padding: 20px;
        }

        h1 {
            font-size: 36px;
            color: #333;
        }

        p {
            font-size: 18px;
            color: #666;
            margin-top: 10px;
        }

        .container {
            width: 100%;
        }

        .emoji {
            font-size: 72px;
            margin-bottom: 20px;
        }

        .box {
            box-shadow: 0 0 16px rgba(0, 0, 0, .1);
            padding: 15px 20px;
            border-radius: 15px;
        }

        .highlight {
            color: #f44336;
            font-weight: bold;
            margin: 10px 0px;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .quote {
            color: #777;
            margin-top: 20px;
        }

        .quote p {
            font-size: 14px;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="emoji">😱</div>
        <h1>Oops!</h1>
        <div class="box">
            <h3 class="highlight" title="<?= $title ?>"><?= $title ?? "Error!" ?></h3>
            <div class="quote">
                <p><?= $message  ?? "" ?></p>
            </div>
        </div>
    </div>
</body>

</html>