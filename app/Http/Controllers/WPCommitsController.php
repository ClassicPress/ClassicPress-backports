<?php
namespace App\Http\Controllers;
use App\WPCommits;
use Illuminate\Http\Request;
class WPCommitsController extends Controller
{
    /**
     * Gets all latest commits from branch (4.9 hardcoded)
     * Pre-fills last shared commit details
     */
    public function index()
    {
      if (count(WPCommits::all()) < 1 ) {
        $shared_sha = 'f4f66b96b19d3a0442e6b4f54fa41455826e2181';
        $curl = curl_init();
        curl_setopt_array($curl, array(
          CURLOPT_URL => "https://api.github.com/repos/WordPress/WordPress/commits/$shared_sha",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "GET",
          CURLOPT_HTTPHEADER => array(
            "Cache-Control: no-cache",
            "User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.103 Safari/537.36"
          ),
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
          echo "cURL Error #:" . $err;
        } else {
          $response = json_decode($response, true);
          $inital = new WPCommits;
          $inital->sha = $response['sha'];
          $inital->nodeid = $response['node_id'];
          $inital->message = $response['commit']['message'];
          $inital->html_link = $response['html_url'];
          $inital->commit_date = $response['commit']['committer']['date'];
          $inital->status = 1;
          $inital->save();
        }
      }
      $last_sha = last_sha('4.9-branch');
      $last_commit =
      $curl = curl_init();
      curl_setopt_array($curl, array(
      CURLOPT_URL => "https://api.github.com/repos/wordpress/wordpress/commits?per_page=100&sha=$last_sha",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "GET",
      CURLOPT_HTTPHEADER => array(
        "Cache-Control: no-cache",
        "User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.103 Safari/537.36"
      ),
      ));
      $response = curl_exec($curl);
      $err = curl_error($curl);
      curl_close($curl);
      if ($err) {
      echo "cURL Error #:" . $err;
      } else {
        $response = json_decode($response, true);
        if(count(WPCommits::all()) == 1 ){
          $array_num = searchArray($response, 'sha', 'f4f66b96b19d3a0442e6b4f54fa41455826e2181');
          for ($i=0; $i < $array_num; $i++) {
            $commit_new = new WPCommits;
            $commit_new->sha = $response[$i]['sha'];
            $commit_new->nodeid = $response[$i]['node_id'];
            $commit_new->message = $response[$i]['commit']['message'];
            $commit_new->html_link = $response[$i]['html_url'];
            $commit_new->commit_date = $response[$i]['commit']['committer']['date'];
            $commit_new->status = 0;
            $commit_new->save();
          }
        }else {
          $last_inseted = WPCommits::orderBy('id', 'desc')->first();
          $array_num = searchArray($response, 'sha', '$last_inseted');
          for ($i=0; $i < $array_num; $i++) {
            $commit = new WPCommits;
            $commit->sha = $response[$i]['sha'];
            $commit->nodeid = $response[$i]['node_id'];
            $commit->message = $response[$i]['commit']['message'];
            $commit->html_link = $response[$i]['html_url'];
            $commit->commit_date = $response[$i]['commit']['committer']['date'];
            $commit->status = 0;
            $commit->save();
          }
        }
      }
    }
}
function last_sha($branch)
{
  $curl = curl_init();
  curl_setopt_array($curl, array(
    CURLOPT_URL => "https://api.github.com/repos/WordPress/WordPress/branches",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "GET",
    CURLOPT_HTTPHEADER => array(
      "Cache-Control: no-cache",
      "User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.103 Safari/537.36"
    ),
  ));
  $response = curl_exec($curl);
  $err = curl_error($curl);
  curl_close($curl);
  if ($err) {
    echo "cURL Error #:" . $err;
  } else {
    $response = json_decode($response, true);
    foreach ($response as $inner) {
      if ($inner['name'] == $branch) {
        $last_sha = $inner['commit']['sha'];
        break;
      }
    }
    return $last_sha;
  }
}
function searchArray($array, $key, $search)
{
  $count = 0;
  foreach($array as $object) {
      if(is_object($object)) {
         $object = get_object_vars($object);
      }
      if ($object[$key] === $search) {
        return $count;
      }
      $count++;
  }
return 'false';
}
