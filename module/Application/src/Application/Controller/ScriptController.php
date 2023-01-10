<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

function rmdir_recursive($dir) {
	$result = true;
	foreach(scandir($dir) as $file) {
		if ('.' === $file || '..' === $file) continue;
		if (is_dir("$dir/$file")) $result &= rmdir_recursive("$dir/$file");
		else $result &= unlink("$dir/$file");
	}
	$result &= rmdir($dir);
	return $result;
}


class ScriptController extends WebDrafterControllerBase
{
    public function deduplicateArtAction()
    {
	die();
    	$viewModel = new ViewModel();
			$viewModel->setTerminal(true);
			
    	$sm = $this->getServiceLocator();
    	$cardTable = $sm->get('Application\Model\CardTable');
			
		  $config = $this->getServiceLocator()->get('Config');
			$dataDir = $config["data_dir"];
			//if($this->auth->getUser()->userId !== 1) {
			//	die('Only the admin can do this');
			//}

			echo "Starting<br/>";
			$bulkIndex = 0;
		  //while(true) {
				echo "<br/>";
				echo "Bulk $bulkIndex<br/>";
				flush();
				$cards = $cardTable->getCardsWithoutArtHash();
				if(count($cards) === 0) {
					die("Finished");
				}
				//echo "Found " . count($cards) . " cards<br/>";

				foreach($cards as $card) {
					//flush();

					$artUrl = urldecode($card->artUrl);
					//echo "Card $card->cardId $card->name $artUrl<br/>";

					if(stripos ($artUrl, "http://googledrive.com/") !== false) {
						//echo "Google drive, skip<br/>";
						continue;
					}
					
					$actualFilePath = $dataDir . substr($artUrl, strlen("/upload/"));
					if(!file_exists($actualFilePath)) {
						//echo "Does not exist at $actualFilePath, skip<br/>";
						continue;
					}

					$hash = md5_file($actualFilePath);
					if($hash === false) {
						//echo "Could not calculate hash<br/>";
						continue;
					}

					$foundCard = $cardTable->getCardByArtHash($hash);
					if($foundCard != null) {
						//echo "Card found with hash $hash<br/>";
						$card->artHash = $hash;
						$card->artUrl = $foundCard->artUrl;
						$cardTable->saveCard($card);

						unlink($actualFilePath);
						//echo "deleted $actualFilePath<br/>";

						//echo "D";
					} else {
						//echo "Card not found with hash $hash, updating hash.<br/>";
						$card->artHash = $hash;
						$cardTable->saveCard($card);
						//echo "N";	
					}

					//$existingCard = $cardTable->getCardByHash($card->)
				}

				$bulkIndex++;
			//}

    	
      die("Finished<br/>");
    }
    
    public function deleteArtsAction()
    {
	die();
			echo "start";
			$config = $this->getServiceLocator()->get('Config');
			$dataDir = $config["data_dir"];
			rmdir_recursive($dataDir);

			/*set_time_limit(600);
    	$setUrlName = $this->getEvent()->getRouteMatch()->getParam('url_name');
    	
    	$sm = $this->getServiceLocator();
    	$setTable = $sm->get('Application\Model\SetTable');
    	$setVersionTable = $sm->get('Application\Model\SetVersionTable');
    	$cardTable = $sm->get('Application\Model\CardTable');
    	$userTable = $sm->get('Application\Model\UserTable');
			$config = $this->getServiceLocator()->get('Config');
			$dataDir = $config["data_dir"];
    	
			$results = $cardTable->getCardsForArtDeletion();

			echo "Starting " . count($result) . "<br>";

			$i = 0;
			foreach($results as $row) {
				$str = urldecode($row->art_url);
		
				$path = "/web/htdocs3/planesculptorsnet/home/www/" . $str;
				try {

					$result = unlink($path);
					
				} catch (Exception $e) {
			
					echo "Could not delete $str $e";
			
				}

				$cardTable->updateCardToNoArt($row->card_id);

				//$row->art_url
				$i++;
				if ($i % 1000 == 0) {
					echo $i . " " . $path . "<br>";
					flush();
				}

				if($i == 100000) die("DONE-$i");
				//die("doneX");
			}

			//var_dump($result);*/
    	
    	die("done2");
    }
    
    
}
