<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;

// TODO must treat the error when user leaves the page when video is downloading and mp3 file(s)/archive remains in temp

class bc extends Controller
{
	public $TEMPFILES = [];
	public $TEMPFOLDERS = [];
	public function getVIDEOID($url)
	{
		// https://www.youtube.com/watch?v=wKBhAG2SNKE
		if(stripos($url, 'youtube.com') !== false) {
			$parseURL = (object)parse_url($url);
			
			try {
				foreach(explode('&', $parseURL->query) as $_GET) {
					try {
						parse_str($_GET);
						$__i = explode('=', $_GET);
						$__i = reset($__i);
						
						if(strtolower($__i) === 'v') return $$__i;
					}
					catch(\Exception $e) { /* failed to decode $_GET */ }
				}
			}
			catch(\Exception $e) { /* query is missing */ }
		}
		
		// https://youtu.be/wKBhAG2SNKE
		if(stripos($url, 'youtu.be') !== false) {
			$parseURL = (object)parse_url($url);
			
			try {
				$__i = explode('/', substr($parseURL->path, 1));
				$__i = reset($__i);
				return $__i;
			}
			catch(\Exception $e) { /* failed to decode $parseURL->path */ }
		}
		
		return false;
	}
	public function getLIST($url)
	{
		$parseURL = (object)parse_url($url);
		
		try {
			foreach(explode('&', $parseURL->query) as $_GET) {
				try {
					parse_str($_GET);
					$__i = explode('=', $_GET);
					$__i = reset($__i);
					
					if(strtolower($__i) === 'list') return $$__i;
				}
				catch(\Exception $e) { /* failed to decode $_GET */ }
			}
		}
		catch(\Exception $e) { /* query is missing */ }
		
		return false;
	}
	public function getVIDEOINFO($URLID)
	{
		$videoINFOURL = "http://www.youtube.com/get_video_info?&video_id={$URLID}&asv=0&el=detailpage";
		$ch = curl_init(); 
		curl_setopt($ch, CURLOPT_URL, $videoINFOURL); 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
		$videoINFO = curl_exec($ch); 
		curl_close($ch);
		
		$response = [];
		try {
			foreach(explode('&', $videoINFO) as $_GET) {
				try {
					parse_str($_GET);
					$__i = explode('=', $_GET);
					$__i = reset($__i);
					$response[$__i] = $$__i;
				}
				catch(\Exception $e) { /* failed to decode $_GET */ }
			}
		}
		catch(\Exception $e) { return false; /* failed to explode response */ }
		
		return (object)$response;
	}
	public function getVIDEOLISTINFO($LIST)
	{
		$listINFOURL = "https://www.googleapis.com/youtube/v3/playlistItems?part=snippet&maxResults=50&playlistId={$LIST}&key=AIzaSyAafA2Hdbh9KO0yx24UwZBFopeWV_2_Mlg";
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $listINFOURL);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
		$listINFO = curl_exec($ch); 
		curl_close($ch);
		
		$videoLIST = [];
		try {
			$listINFO = json_decode($listINFO);
			if(isset($listINFO->error)) {
				// failed to take the list
				return false;
			}
			
			foreach($listINFO->items as $item) {
				try {
					$videoLIST[] = $item->snippet->resourceId->videoId;
				}
				catch(\Exception $e) { /* failed to get video id */ }
			}
		}
		catch(\Exception $e) { return false; /* failed to decode json */ }
		
		return $videoLIST;
	}
  public function fsearch(Request $request)
	{
		$url = trim($request->get('url'));
		
		$validator = Validator::make(['url' => $url], ['url' => 'required|url']);
		if($validator->passes()) {
			// check for playlist paramenter first
			if($LIST = $this->getLIST($url)) {
				$videoLIST = $this->getVIDEOLISTINFO($LIST);
				
				if($videoLIST === false) {
					return ['passes' => false, 'errors' => ['url' => 'The url is invalid.']];
				}
				
				$videoLISTARRAY = [];
				foreach($videoLIST as $URLID) {
					$videoINFO = $this->getVIDEOINFO($URLID);
					if(($videoINFO === false || isset($videoINFO->status) === false || strtolower($videoINFO->status) === 'fail') === false) {
						$videoLISTARRAY[$URLID] = $videoINFO;
					}
				}
				
				if(empty($videoLISTARRAY)) {
					return ['passes' => false, 'errors' => ['url' => 'Given playlist is empty.']];
				}
				
				return ['passes' => true, 'html' => view('videolist', ['videoLISTARRAY' => $videoLISTARRAY])->render()];
			}
			else {
				$URLID = $this->getVIDEOID($url);
			
				if($URLID === false) {
					return ['passes' => false, 'errors' => ['url' => 'The url is invalid.']];
				}

				$videoINFO = $this->getVIDEOINFO($URLID);

				if($videoINFO === false || isset($videoINFO->status) === false || strtolower($videoINFO->status) === 'fail') {
					return ['passes' => false, 'errors' => ['url' => 'The url is invalid.']];
				}
				
				return ['passes' => true, 'html' => view('video', ['URLID' => $URLID, 'videoINFO' => $videoINFO])->render()];
			}
		}
		
		return ['passes' => false, 'errors' => $validator->messages()];
	}
	public function storagePATH()
	{
		return storage_path('temp/');
	}
	public function generateRandomFILENAME($path, $extension, $length, $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789')
	{
		$filename = substr(str_shuffle($characters), 0, $length);
		if($extension) $filename = "{$filename}.{$extension}";
		
		if(File::exists("{$path}{$filename}")) {
			return $this->generateRandomFILENAME($path, $extension, $length, $characters);
		}
		
		return $filename;
	}
	public function downloadFILE($url)
	{
		try {
			set_time_limit(0);
			
			$file = "{$this->storagePATH()}{$this->generateRandomFILENAME($this->storagePATH(), null, 8)}";
			$ch = curl_init();
			curl_setopt_array($ch, [
				CURLOPT_FILE		=> $fp = fopen($file, 'w'),
				CURLOPT_TIMEOUT	=> 30000,
				CURLOPT_URL			=> $url,
			]);
			curl_exec($ch);
			curl_close($ch);
			fclose($fp);
			
			if(File::exists($file)) {
				$this->TEMPFILES[] =  $file;
				return $file;
			}
		}
		catch(\Exception $e) { /* failed to download file from given url */ }
		
		return false;
	}
	public function getVIDEOURL($videoINFO)
	{
		try {
			// take first video url, it has better quality
			foreach(explode(',', $videoINFO->url_encoded_fmt_stream_map) as $format) {
				parse_str($format);
				$videURL = urldecode($url)."&title=tmpfile";
				
				return $videURL;
			}
		}
		catch(\Exception $e) { /* failed to decode $videoINFO->url_encoded_fmt_stream_map */ }
		
		return false;
	}
	public function mp3CONVERT($videoFILE)
	{
		$mp3FILE = "{$this->storagePATH()}{$this->generateRandomFILENAME($this->storagePATH(), 'mp3', 8)}";
		exec('"'.base_path('ffmpeg/ffmpeg').'" -i "'.$videoFILE.'" -b:a 192K -vn "'.$mp3FILE.'"');
		
		return $mp3FILE;
	}
	public function setTOKEN($tokenNAME, $tokenVALUE, $httpONLY = true, $secure = false)
	{
    setcookie($tokenNAME, $tokenVALUE, time() + 3600 /* expire in 1 hour */, '/', $_SERVER['HTTP_HOST'], $secure, $httpONLY);
  }
	public function clearTOKEN(Request $request)
	{
    setcookie($request->get('name'), null, time() - 3600 /* expired 1 hour ago */, '/', $_SERVER['HTTP_HOST'], false, false);
  }
	public function download($videoID, Request $request)
	{
		if($videoINFO = $this->getVIDEOINFO($videoID)) {
			if($videoINFO === false || isset($videoINFO->status) === false || strtolower($videoINFO->status) === 'fail') {
				return 'Whoops! An error occurred.';
			}
			
			if($videURL = $this->getVIDEOURL($videoINFO)) {
				if($videoFILE = $this->downloadFILE($videURL)) {
					try {
						$mp3FILE = $this->mp3CONVERT($videoFILE);
						
						// set up cookie to announce js that download started
						$this->setTOKEN($request->get('t'), $request->get('tv'), false);
						return response()->download($mp3FILE, "{$videoINFO->title}.mp3")->deleteFileAfterSend(true);
					}
					catch(\Exception $e) { /* failed to convert video to mp3 */ }
				}
			}
		}
		
		return 'Whoops! An error occurred.';
	}
	public function downloadlist(Request $request)
	{
		if($videos = $request->get('__v_')) {
			$videosCOUNT = count($videos);
			$mp3FILEARRAY = [];
			
			$i = 0;
			// put download progress inside temporary file
			$progressFILE = "{$this->storagePATH()}{$request->get('i')}";
			$this->TEMPFILES[] = $progressFILE;
			File::put($progressFILE, "{$i}/{$videosCOUNT}");
			
			foreach($videos as $videoID) {
				if($videoINFO = $this->getVIDEOINFO($videoID)) {
					if(($videoINFO === false || isset($videoINFO->status) === false || strtolower($videoINFO->status) === 'fail') === false) {
						if($videURL = $this->getVIDEOURL($videoINFO)) {
							if($videoFILE = $this->downloadFILE($videURL)) {
								try {
									$mp3FILE = $this->mp3CONVERT($videoFILE);
									$mp3FILEARRAY[] = (object)['title' => $videoINFO->title, 'file' => $mp3FILE];
								}
								catch(\Exception $e) { /* failed to convert video to mp3 */ }
								
								$i++;
								File::put($progressFILE, "{$i}/{$videosCOUNT}");
							}
						}
					}
				}
			}
			
			if(count($mp3FILEARRAY)) {
				// create an directory and move all mp3 files inside, renamed with video title
				$archiveDIRECTORY = "{$this->storagePATH()}{$this->generateRandomFILENAME($this->storagePATH(), null, 8)}";
				mkdir($archiveDIRECTORY, 0777, true);
				$this->TEMPFOLDERS[] = $archiveDIRECTORY;
				
				foreach($mp3FILEARRAY as $mp3FILE) {
					try {
						$this->TEMPFILES[] = "{$archiveDIRECTORY}/{$mp3FILE->title}.mp3";
						rename($mp3FILE->file, "{$archiveDIRECTORY}/{$mp3FILE->title}.mp3");
					}
					catch(\Exception $e) { /* mp3 file misses */ }
				}
				
				// archive mp3 files
				$archive = "{$this->storagePATH()}{$this->generateRandomFILENAME($this->storagePATH(), 'zip', 8)}";
				\Zipper::make($archive)->add(glob("{$archiveDIRECTORY}/*"))->close();
				
				// set up cookie to announce js that download started
				$this->setTOKEN($request->get('t'), $request->get('tv'), false);
				return response()->download($archive)->deleteFileAfterSend(true);
			}
		}
		
		return 'Whoops! An error occurred.';
	}
	public function getPROGRESS($filename)
	{
		// take download progress from temporary file
		if(File::exists("{$this->storagePATH()}{$filename}")) {
			return File::get("{$this->storagePATH()}{$filename}");
		}
		
		return null;
	}
	public function __destruct()
	{
		// delete temporary files
		foreach($this->TEMPFILES as $temp) {
			if(File::exists($temp)) {
				File::delete($temp);
			}
		}
		
		// delete temporary folders
		foreach($this->TEMPFOLDERS as $temp) {
			if(is_dir($temp)) {
				rmdir($temp);
			}
		}
	}
}
