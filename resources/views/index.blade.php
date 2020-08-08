<!doctype html>
<html lang="{{ app()->getLocale() }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{csrf_token()}}">
        <!-- Latest compiled and minified CSS -->
        <link rel="stylesheet" href="https://unpkg.com/tailwindcss@0.4.0/dist/tailwind.min.css">
        <link rel="stylesheet" href="{{URL::asset('css/app.css')}}">
        <title>ClassicPress Backports</title>

        <!-- Fonts -->
        <link href="https://fonts.googleapis.com/css?family=Nunito:200,600" rel="stylesheet" type="text/css">
        <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>

    </head>
    <body>

<div class="font-sans bg-grey-lighter flex flex-col min-h-screen">
  <div>
    <div class="bg-white">
      <div class="container mx-auto px-4">
        <div class="flex items-center md:justify-between py-4">
          <div class="w-1/4 md:hidden">
          </div>
          <div class="w-1/2 md:w-auto text-center text-black text-2xl font-medium">
            <a href="/">ClassicPress Backports</a>
          </div>
          @if($user)
            <div class="w-1/4 md:w-auto md:flex text-right">
              <div>
                <img class="inline-block h-8 w-8 rounded-full" src="{{$user->avatar}}" alt="">
              </div>
              <div class="hidden md:block md:flex md:items-center ml-2">
                <span class="text-grey-dark text-sm mr-1">
                  Logged in as
                  <a
                    href="https://github.com/{{$user->username}}"
                    target="_blank"
                    rel="noopener noreferrer"
                  >{{"@"}}{{$user->username}}</a>
                  |
                </span>

                <a href="{{ route('logout') }}" class="text-black text-sm mr-1 hover:text-blue no-underline">Log out</a>
              </div>
            </div>
          @else
            <div class="w-1/4 md:w-auto md:flex text-right">
              <div>
                <img class="inline-block h-8 w-8 rounded-full" src="https://www.classicpress.net/wp-admin/images/wordpress-logo.svg" alt="">
              </div>
              <div class="hidden md:block md:flex md:items-center ml-2">
                <a href="{{ route('login') }}" class="text-black text-sm mr-1 hover:text-blue no-underline">Log in</a>
              </div>
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>
  <div class="flex-grow container mx-auto sm:px-4 pt-6 pb-8">
    <div class="flex flex-wrap -mx-4">
      <div class="w-full mb-6 lg:mb-0 px-4 flex flex-col">
        <div class="flex-grow flex flex-col bg-white border-t border-b sm:rounded sm:border shadow overflow-hidden">
          <div class="px-6 -mb-px">
            <h3 class="text-blue-dark py-4 font-normal text-lg">
              Available branches:
            </h3>
            <ul class="text-lg">
              <?php foreach ($branches as $branch) { ?>
                <li><a href="/branches/wp-{{$branch}}">WordPress {{$branch}}</a></li>
              <?php } ?>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="bg-white border-t">
    <div class="container mx-auto px-4">
      <div class="md:flex justify-between items-center text-sm">
        <div class="text-center md:text-left py-3 md:py-4 border-b md:border-b-0">
          <a href="https://classicpress.net" target="_blank"class="no-underline text-grey-dark mr-4">Home</a>
        </div>
        <div class="md:flex md:flex-row-reverse items-center py-4">
          <div class="text-grey text-center md:mr-4">&copy; <?php echo date("Y"); ?> ClassicPress</div>
        </div>
      </div>
    </div>
  </div>
</div>

    </body>
</html>
