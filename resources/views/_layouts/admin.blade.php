<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>车123_管理后台</title>

    <link href="/css/app.css" rel="stylesheet">
    <link href="/vendor/DataTables/css/jquery.dataTables.min.css"rel="stylesheet">
    <link href="/vendor/DataTables/css/dataTables.bootstrap.min.css"rel="stylesheet">
    <link href="/vendor/Bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="/vendor/Bootstrap/css/bootstrap-theme.min.css" rel="stylesheet">

    <script src="/vendor/jQuery/jquery-2.1.4.min.js"></script>

</head>
<body>
<nav class="navbar navbar-default">
    <div class="container-fluid">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
                <span class="sr-only">Toggle Navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand" href="#">车123</a>
        </div>

        <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
            <ul class="nav navbar-nav">
                <li><a href="/admin">后台首页</a></li>
            </ul>

            <ul class="nav navbar-nav navbar-right">
                @if (Auth::guest())
                <li><a href="/auth/login">Login</a></li>
                <li><a href="/auth/register">Register</a></li>
                @else
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">{{ Auth::user()->name }} <span class="caret"></span></a>
                    <ul class="dropdown-menu" role="menu">
                        <li><a href="/auth/logout">Logout</a></li>
                    </ul>
                </li>
                @endif
            </ul>
        </div>
    </div>
</nav>

@yield('content')

<!-- Scripts -->
<script src="/vendor/Bootstrap/js/bootstrap.min.js"></script>
<script src="/vendor/DataTables/js/jquery.dataTables.min.js"></script>
</body>
</html>  