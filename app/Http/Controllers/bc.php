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
	public function getVIDEOLISTIDS($LIST)
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
				$videoLIST = $this->getVIDEOLISTIDS($LIST);
				
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
	public function downloadFILE($url, $videoFILENAME = null)
	{
		try {
			set_time_limit(0);
			
			$file = $videoFILENAME ?: "{$this->storagePATH()}{$this->generateRandomFILENAME($this->storagePATH(), null, 8)}";
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
				$this->TEMPFILES[] = $file;
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
	public function mp3CONVERT($videoFILE, $mp3FILENAME = null)
	{
		$mp3FILE = $mp3FILENAME ?: "{$this->storagePATH()}{$this->generateRandomFILENAME($this->storagePATH(), 'mp3', 8)}";
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
	public function sanitize($string, $replaceSPACES = false, $forceLOWERCASE = false, $removeNONALPHANUMERIC = false) {
    $strip = ['~', '`', '!', '@', '#', '$', '%', '^', '&', '*', '(', ')', '_', '=', '+', '[', '{', ']',
							'}', '\\', '|', ';', ':', '"', '\'', '&#8216;', '&#8217;', '&#8220;', '&#8221;', '&#8211;', '&#8212;',
							'â€”', 'â€“', ',', '<', '.', '>', '/', '?'];
    $clean = trim(str_replace($strip, null, strip_tags($string)));
    $clean = ($replaceSPACES) ? preg_replace('/\s+/', '-', $clean) : $clean;
    $clean = ($removeNONALPHANUMERIC) ? preg_replace('/[^a-zA-Z0-9]/', null, $clean) : $clean ;
    return ($forceLOWERCASE) ? (function_exists('mb_strtolower')) ? mb_strtolower($clean, 'UTF-8') : strtolower($clean) : $clean;
	}
	public function calculatePROGRESS($totalSIZE, $currentSIZE)
	{
		if($currentSIZE > $totalSIZE) {
			return 100;
		}
		
		if($totalSIZE === 0) {
			return 0;
		}
		else {
			return (int)($currentSIZE * 100 / $totalSIZE);
		}
	}
	public function getFILESIZE($url)
	{
		// assume failure
		$result = -1;

		try {
			$ch = curl_init($url);

			// issue a HEAD request and follow any redirects.
			curl_setopt($ch, CURLOPT_NOBODY, true);
			curl_setopt($ch, CURLOPT_HEADER, true);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

			$details = curl_exec($ch);
			curl_close($ch);

			if($details) {
				$contentLENGTH = 'unknown';
				$status = 'unknown';

				if(preg_match('/^HTTP\/1\.[01] (\d\d\d)/', $details, $matches)) {
					$status = (int)$matches[1];
				}

				if(preg_match('/Content-Length: (\d+)/', $details, $matches)) {
					$contentLENGTH = (int)$matches[1];
				}

				if($status === 200 || ($status > 300 && $status <= 308)) {
					$result = $contentLENGTH;
				}
			}
		}
		catch(\Exception $e) { /* failed to take file details */ }

		return $result;
	}
	public function download($videoID, Request $request)
	{
		if($videoINFO = $this->getVIDEOINFO($videoID)) {
			if($videoINFO === false || isset($videoINFO->status) === false || strtolower($videoINFO->status) === 'fail') {
				return 'Whoops! An error occurred.';
			}
			
			if($videURL = $this->getVIDEOURL($videoINFO)) {
				// determine video length
				$videoLENGTH = (int)$videoINFO->length_seconds;
				
				$videoFILENAME = "{$this->storagePATH()}{$this->generateRandomFILENAME($this->storagePATH(), null, 8)}";
				$mp3FILENAME = "{$this->storagePATH()}{$this->generateRandomFILENAME($this->storagePATH(), 'mp3', 8)}";
				
				// collect progress information
				$progressINFO = (object)[
					'multiple' => false,
					'videoSIZE' => $this->getFILESIZE($videURL),
					'videoLENGTH' => $videoLENGTH,
					'videoFILE' => $videoFILENAME,
					'mp3FILE' => $mp3FILENAME
				];
				
				$progressFILE = "{$this->storagePATH()}{$request->get('i')}";
				$this->TEMPFILES[] = $progressFILE;
				File::put($progressFILE, json_encode($progressINFO));
				
				if($videoFILE = $this->downloadFILE($videURL, $videoFILENAME)) {
					try {
						$mp3FILE = $this->mp3CONVERT($videoFILE, $mp3FILENAME);
						
						// set up cookie to announce js that download started
						$this->setTOKEN($request->get('t'), $request->get('tv'), false);
						return response()->download($mp3FILE, "{$this->sanitize($videoINFO->title)}.mp3")->deleteFileAfterSend(true);
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
			$videosSIZE = 0;
			$videosLENGTH = 0;
			$videoINFOARRAY = [];
			
			foreach($videos as $videoID) {
				if($videoINFO = $this->getVIDEOINFO($videoID)) {
					if(($videoINFO === false || isset($videoINFO->status) === false || strtolower($videoINFO->status) === 'fail') === false) {
						if($videURL = $this->getVIDEOURL($videoINFO)) {
							$videosLENGTH += (int)$videoINFO->length_seconds;
							$videosSIZE += $this->getFILESIZE($videURL);
							$videoINFOARRAY[] = (object)[
								'videoURL' => $videURL,
								'title' => $videoINFO->title
							];
						}
					}
				}
			}
			
			// create an directory for playlist
			$archiveDIRECTORY = "{$this->storagePATH()}{$this->generateRandomFILENAME($this->storagePATH(), null, 8)}";
			mkdir($archiveDIRECTORY, 0777, true);
			$this->TEMPFOLDERS[] = $archiveDIRECTORY;
			
			// collect progress information
			$progressINFO = (object)[
				'multiple' => true,
				'videosSIZE' => $videosSIZE,
				'videosLENGTH' => $videosLENGTH,
				'videosDIR' => $archiveDIRECTORY
			];
			
			$progressFILE = "{$this->storagePATH()}{$request->get('i')}";
			$this->TEMPFILES[] = $progressFILE;
			File::put($progressFILE, json_encode($progressINFO));
			
			foreach($videoINFOARRAY as $videoINFO) {
				$videoFILENAME = "{$archiveDIRECTORY}/{$this->generateRandomFILENAME($archiveDIRECTORY, null, 8)}";
				$mp3FILETITTLE = $this->sanitize($videoINFO->title);
				$mp3FILENAME = "{$archiveDIRECTORY}/{$mp3FILETITTLE}.mp3";
				if($videoFILE = $this->downloadFILE($videoINFO->videoURL, $videoFILENAME)) {
					try {
						$mp3FILE = $this->mp3CONVERT($videoFILE, $mp3FILENAME);
						$this->TEMPFILES[] = $mp3FILENAME;
					}
					catch(\Exception $e) { /* failed to convert video to mp3 */ }
				}
			}
			
			// archive mp3 files
			$archive = "{$this->storagePATH()}{$this->generateRandomFILENAME($this->storagePATH(), 'zip', 8)}";
			\Zipper::make($archive)->add(glob("{$archiveDIRECTORY}/*.mp3"))->close();

			// set up cookie to announce js that download started
			$this->setTOKEN($request->get('t'), $request->get('tv'), false);
			return response()->download($archive)->deleteFileAfterSend(true);
		}
		
		return 'Whoops! An error occurred.';
	}
	public function getMP3SIZE($videoLENGTH)
	{
		// 1s to mp3 is around 24000 bytes
		return $videoLENGTH * 23500;
	}
	public function getDIRSIZE($DIR)
	{
		$size = 0;

		if(is_dir($DIR)) {
			$files = glob("{$DIR}/*");
			foreach($files as $path){
				if(is_dir($path)) {
					$size += $this->getDIRSIZE($path);
				}
				else {
					$size += File::size($path);
				}
			}
		}

		return $size;
	}
	public function getPROGRESS($filename)
	{
		// take download progress from temporary file
		$progressFILE = "{$this->storagePATH()}{$filename}";
		if(File::exists($progressFILE)) {
			try {
				$progressINFO = json_decode(File::get($progressFILE));
				if($progressINFO->multiple) {
					$totalSIZE = $progressINFO->videosSIZE + $this->getMP3SIZE($progressINFO->videosLENGTH);
					$currentSIZE = 0;
					
					if(is_dir($progressINFO->videosDIR)) {
						$currentSIZE += $this->getDIRSIZE($progressINFO->videosDIR);
					}

					return $this->calculatePROGRESS($totalSIZE, $currentSIZE);
				}
				else {
					$totalSIZE = $progressINFO->videoSIZE + $this->getMP3SIZE($progressINFO->videoLENGTH);
					$currentSIZE = 0;

					if(File::exists($progressINFO->videoFILE)) {
						$currentSIZE += File::size($progressINFO->videoFILE);
					}

					if(File::exists($progressINFO->mp3FILE)) {
						$currentSIZE += File::size($progressINFO->mp3FILE);
					}

					return $this->calculatePROGRESS($totalSIZE, $currentSIZE);
				}
			}
			catch(\Exception $e) { /* failed decode progress file */ }
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
