<style>
    .clearfix:after {
        content: "";
        display: table;
        clear: both;
    }

    a {
        color: #5D6975;
        text-decoration: underline;
    }

    body {
        position: relative;
        width: 15cm;
        height: 29.7cm;
        margin: 0 auto;
        color: #001028;
        background: #FFFFFF;
        font-family: Arial, sans-serif;
        font-size: 8px;
        font-family: Arial;
        border: darkgrey solid 1px;
    }

    header {
        padding: 0 0;
        margin-bottom: 30px;
    }

    #logo {
        text-align: center;
        margin-bottom: 10px;
    }

    #logo img {
        width: 90px;
    }

    h1 {
        border-top: 1px solid  #5D6975;
        border-bottom: 1px solid  #5D6975;
        color: #5D6975;
        font-size: 1.4em;
        line-height: 1.8em;
        font-weight: normal;
        text-align: center;
        margin: 0 0 20px 0;
        background: url(/assets/dimension.png);
    }
    .pharmacy {
            color: #5D6975;
            font-size: 2.4em;
            line-height: 1.4em;
            font-weight: normal;
            text-align: center;
            margin: 0 0 20px 0;
        }

    .branch_area {
            color: #5D6975;
            font-size: 1.2em;
        }

    #project {
        float: left;
    }

    #project span {
        color: #5D6975;
        text-align: right;
        width: 52px;
        margin-right: 10px;
        display: inline-block;
        font-size: 0.8em;
    }

    #company {
        float: right;
    }

    #project div,
    #company div {
        white-space: nowrap;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        border-spacing: 0;
        margin-bottom: 20px;
    }

    table tr:nth-child(2n-1) td {
        background: #F5F5F5;
    }

    table th,
    table td {
        text-align: center;
    }

    table th {
        padding: 5px 20px;
        color: #5D6975;
        border-bottom: 1px solid #C1CED9;
        white-space: nowrap;
        font-weight: normal;
    }

    table .unit,
    table .qty
    {
        text-align: center;
    }

    table .service,
    table .desc {
        text-align: left;
    }
    table th .desc,
    table td .desc{
        text-align: left;
    }

    table td {
        padding: 5px;
        text-align: right;
    }

    table td.service,
    table td.desc {
        vertical-align: top;
    }

    table td.unit,
    table td.qty,
    table td.total {
        font-size: 1.2em;
    }

    table td.grand {
        border-top: 1px solid #5D6975;;
    }

    #notices .notice {
        color: #5D6975;
        font-size: 1.2em;
    }

    footer {
        color: #5D6975;
        width: 100%;
        height: 30px;
        position: absolute;
        bottom: 0;
        border-top: 1px solid #C1CED9;
        padding: 8px 0;
        text-align: center;
    }
</style>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Example 1</title>
    <link rel="stylesheet" href="style.css" media="all" />
</head>
<body>
<header class="clearfix">
    <div id="logo">
        <span class="pharmacy">{{$order['pharmacy']}}</span>
        <br>
        <span class="branch_area">{{$order['branch_area']}}, {{$order['branch_city']}}</span>
    </div>

    <h1>INVOICE: {{$order['invoice']}}</h1>
    <div id="company" class="clearfix">
        <div>{{$order['created_by']}}</div>
        <div>{{$order['pharmacy_address']}}</div>
        <div>{{$order['user_email']}}</div>
    </div>
    <div id="project">
        <div><span>COMPANY</span> {{$order['company']}}</div>
        <div><span>MR NAME</span> {{$order['mr_name']}} </div>
        <div><span>DATE</span> {{$order['created_at']}}</div>
    </div>
</header>
<main>
    <table>
        <thead>
        <tr>
            <th style="width:5%" class="unit">SI</th>
            <th class="desc">DESCRIPTION</th>
            <th style="width:5%">QTY(BOX)</th>
        </tr>
        </thead>
        <tbody>
        @foreach($order['order_items'] as $item)
        <tr>
            <td class="unit">{{$order['no']++}}</td>
            <td class="service">{{$item['medicine']}} ({{$item['medicine_power']}} {{$item['medicine_type']}})</td>
            <td class="qty">{{$item['quantity']}}</td>
        </tr>
        @endforeach

{{--
        <tr>
            <td colspan="2">SUBTOTAL</td>
            <td class="total">$5,200.00</td>
        </tr>
        <tr>
            <td colspan="2">TAX 25%</td>
            <td class="total">$1,300.00</td>
        </tr>
        <tr>
            <td colspan="2" class="grand total">GRAND TOTAL</td>
            <td class="grand total">$6,500.00</td>
        </tr>
        --}}
        </tbody>
    </table>
{{--    <div id="notices">--}}
{{--        <div>NOTICE:</div>--}}
{{--        <div class="notice">A finance charge of 1.5% will be made on unpaid balances after 30 days.</div>--}}
{{--    </div>--}}
</main>
{{--<footer>--}}
{{--    Invoice was created on a computer and is valid without the signature and seal.--}}
{{--</footer>--}}
</body>
</html>
