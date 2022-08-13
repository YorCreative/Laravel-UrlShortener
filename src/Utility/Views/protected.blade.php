<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link href="https://getbootstrap.com/docs/5.0/examples/sign-in/signin.css" rel="stylesheet">
    <title>{{ config('urlshortener.branding.views.protected.content.title') }}</title>
</head>
<body class="text-center">
<main class="form-signin">
    <form action="{{ route('urlshortener.attempt.protected') }}" method="POST">
        @csrf
        <input type="hidden" name="identifier" value="{{ $identifier }}">

        <img class="mb-4" src="{{ config('urlshortener.branding.views.protected.images.image-1') }}" alt="" width="82"
             height="72">
        <h1 class="h3 mb-3 fw-normal">Password Protected</h1>

        <div class="form-floating">
            <input type="password" name="password" class="form-control" id="floatingPassword" placeholder="Password">
            <label for="floatingPassword">Password</label>
            @if($errors)
                @error('password')
                <div
                    class="alert alert-danger">{{ config('urlshortener.branding.views.protected.content.message') }}</div>
                @enderror
            @endif
        </div>
        <button class="w-100 btn btn-lg btn-primary" type="submit">Continue</button>
    </form>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM"
        crossorigin="anonymous"></script>
</body>
</html>
