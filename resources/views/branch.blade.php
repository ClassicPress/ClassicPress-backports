<!doctype html>
<html lang="{{ app()->getLocale() }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{csrf_token()}}">
        <!-- Latest compiled and minified CSS -->
        <link rel="stylesheet" href="https://unpkg.com/tailwindcss@0.4.0/dist/tailwind.min.css">
        <link rel="stylesheet" href="{{URL::asset('css/app.css')}}">
        <title>ClassicPress Bots</title>

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
            ClassicPress Bots
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
                <img class="inline-block h-8 w-8 rounded-full" src="https://classicpress.assets-fider.io/images/100/120" alt="">
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
          <div class="border-b">
            <div class="flex justify-between px-6 -mb-px">
              <h3 class="text-blue-dark py-4 font-normal text-lg">
                WordPress commits:
                <code>{{$branch}}</code> branch
              </h3>
            </div>
          </div>

          <!-- Commits list -->
          <?php foreach ($commits as $commit): ?>
            <!-- Commit -->
            <div class="flex-grow flex px-6 py-6 text-grey-darker items-center border-b -mx-4">
              <!-- Commit hash and link -->
              <div class="w-2/5 xl:w-1/6 px-4 flex items-center">
                <span class="text-lg">
                  <a
                    class="no-underline"
                    href="{{$commit->html_link}}"
                    target="_blank"
                    rel="noopener noreferrer"
                  >{{substr($commit->sha, 0, 7)}}</a>
              </span>
              </div>

              <!-- Commit message -->
              <div class="hidden md:flex lg:hidden xl:flex w-2/3 px-4 items-center">
                <pre class="commit-message">{{$commit->message}}</pre>
              </div>

              <!-- Commit actions container -->
              <div class="flex w-1/6 md:w/12">
                @if($user && $user->hasWriteAccess() && $commit->status == 0)
                  <!-- Modal -->
                  <div class="modal fade" id="modal-{{$commit->sha}}" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                      <div class="modal-content">
                        <div class="modal-header">
                          <h5 class="modal-title">Decline commit <?php echo substr($commit->sha, 0, 7); ?></h5>
                          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                          </button>
                        </div>
                        <div class="modal-body">
                          <form class="" action="index.html" method="post">
                            <div class="form-group">
                              <label for="reason">Reason for declining</label>
                              <textarea class="form-control "name="reason" rows="8" cols="80"></textarea>
                            </div>
                            <div class="text-center">
                              <button type="submit" class="btn btn-secondary">Confirm Decline</button>
                              <button type="button" class="btn btn-primary" data-dismiss="modal">Cancel</button>
                            </div>
                          </form>
                        </div>

                      </div>
                    </div>
                  </div>
                @endif

                <!-- Commit actions -->
                <div class="inline-flex">
                  @if($commit->status == 0)
                    <span class="text-grey">
                      No action taken yet
                    </span>
                  @elseif($commit->status == 1)
                    <a href="https://dosomething.com" class="no-underline text-grey" target="_blank">PR opened</a>
                  @else
                    Commit declined: {{$commit->decline_response}}
                  @endif

                  @if($user && $user->hasWriteAccess())
                    @if($commit->status == 0)
                      <a href="http://dosomething.com"><button class="bg-blue hover:bg-grey text-white py-2 px-4 rounded-l">
                        PR
                      </button></a>
                      <button data-toggle="modal" data-target="#modal-{{$commit->sha}}" class="bg-grey-light hover:bg-grey text-grey-darkest py-2 px-4 rounded-r">
                        Decline
                      </button>
                    @endif
                  @endif
                </div>
              </div>
            </div><!-- End commit -->
          <?php endforeach; ?>

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
