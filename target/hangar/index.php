<?php
use n2n\web\http\ResponseCacheControl;
use n2n\io\managed\impl\CommonFile;
use n2n\io\managed\impl\FileFactory;
use n2n\web\http\HttpCacheControl;

$pubPath = dirname(__FILE__);
$appPubPath = realpath($pubPath . '/../../public');

$appPath = 'phar://' . $pubPath . '/hangar.phar/app';
$libPath = 'phar://' . $pubPath . '/hangar.phar/lib';
$appAppPath = realpath($pubPath . '/../../app');
$appLibPath = realpath($pubPath . '/../../lib');

$varPath = realpath($pubPath . '/var');
$appVarPath = realpath($pubPath . '/../../var');

set_include_path(implode(PATH_SEPARATOR,
		array($appPath, $libPath, $appAppPath, $appLibPath, get_include_path())));

if (isset($_SERVER['N2N_STAGE'])) {
	define('N2N_STAGE', $_SERVER['N2N_STAGE']);
}

require_once '../../vendor/n2n/n2n/src/app/n2n/core/TypeLoader.php';

n2n\core\TypeLoader::register(true,
		require __DIR__ . '/../../vendor/composer/autoload_psr4.php',
		require __DIR__ . '/../../vendor/composer/autoload_classmap.php');

n2n\core\N2N::initialize($pubPath, $varPath, new n2n\core\FileN2nCache(), 
		new n2n\core\module\impl\EtcModuleFactory('hangar.app.ini', 'hangar.module.ini'));

if (n2n\core\N2N::isHttpContextAvailable()) {
	$cmdPath = n2n\core\N2N::getHttpContext()->getRequest()->getCmdPath();			

	if ('assets' == $cmdPath->getFirstPathPart(false)) {
		$path = 'phar://' . $pubPath . '/hangar.phar/public/' . $cmdPath->toRealString(false);
		if (is_file($path)) {
			
			$response = n2n\core\N2N::getHttpContext()->getResponse();
			$response->setHttpCacheControl(new HttpCacheControl(new \DateInterval('P1D')));
			$response->send(FileFactory::createFromFs($path));
	// 		readfile('phar://' . $pubPath . '/hangar.phar/public/' . $cmdPath->toRealString(false));
			return;
		}
	}
}

hangar\Hangar::setup($appVarPath, $appAppPath, $appLibPath, $appPubPath);
n2n\core\N2N::autoInvokeBatchJobs();
n2n\core\N2N::autoInvokeControllers();

n2n\core\N2N::finalize();

function test($value) {
	if (!n2n\core\N2N::isLiveStageOn()) {
		echo "\r\n<pre>\r\n";
		var_dump($value);
		if (is_scalar($value)) echo "\r\n";
		echo "</pre>\r\n";
	}
}