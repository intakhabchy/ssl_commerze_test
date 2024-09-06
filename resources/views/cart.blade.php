<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
</head>
<body>
    <div class="container">
        <div class="row">
            <div class="col-sm-12" style="text-align:center">
                <form action="{{ url('/invoice') }}" method="POST">
                    @csrf
                    <table align="center">
                        <tr>
                            <th>Sl</th>
                            <th>Product</th>
                            <th>Price</th>
                        </tr>
                        @foreach ($cartInfo as $ci)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $ci->product_name }}</td>
                                <td>{{ $ci->price }}</td>
                                <input type="hidden" name="products[{{ $loop->index }}][product_id]" value="{{ $ci->product_id }}">
                                <input type="hidden" name="products[{{ $loop->index }}][product_name]" value="{{ $ci->product_name }}">
                                <input type="hidden" name="products[{{ $loop->index }}][price]" value="{{ $ci->price }}">
                            </tr>
                        @endforeach
                    </table>
                    <button type="submit" class="btn btn-success">Checkout</button>
                </form>       
            </div>
        </div>
    </div>
</body>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
</html>