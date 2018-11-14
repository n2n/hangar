<?php
$pubPath = dirname(__FILE__);

$appRootPath = realpath($pubPath . '/../../../..');
$appPubPath = realpath($appRootPath . '/public');
$appAppPath = realpath($appRootPath . '/app');
$appLibPath = realpath($appRootPath . '/lib');
$appVarPath = realpath($appRootPath . '/var');

$appPath = 'phar://' . $pubPath . '/hangar.phar/app';
$libPath = 'phar://' . $pubPath . '/hangar.phar/lib';
$varPath = realpath($pubPath . '/var');

set_include_path(implode(PATH_SEPARATOR,
		array($appPath, $libPath, $appAppPath, $appLibPath, get_include_path())));

if (isset($_SERVER['N2N_STAGE'])) {
	define('N2N_STAGE', $_SERVER['N2N_STAGE']);
}

require_once __DIR__ . '/../../n2n/src/app/n2n/core/TypeLoader.php';

n2n\core\TypeLoader::register(true,
		require __DIR__ . '/../../../composer/autoload_psr4.php',
		require __DIR__ . '/../../../composer/autoload_classmap.php');

n2n\core\N2N::initialize($pubPath, $varPath, new n2n\core\FileN2nCache(),
		new hangar\core\model\HangarModuleFactory($appVarPath));

if (n2n\core\N2N::isHttpContextAvailable()) {
	$cmdPath = n2n\core\N2N::getHttpContext()->getRequest()->getCmdPath();			

	if ('assets' == $cmdPath->getFirstPathPart(false)) {
		$path = 'phar://' . $pubPath . '/hangar.phar/public/' . $cmdPath->toRealString(false);
		if (!is_file($path)) {
			$path = $appPubPath . DIRECTORY_SEPARATOR . $cmdPath->toRealString(false);
		}
		
		if (is_file($path)) {
			
			$response = n2n\core\N2N::getHttpContext()->getResponse();
			$response->setHttpCacheControl(new n2n\web\http\HttpCacheControl(new \DateInterval('P1D')));
			$response->send(new n2n\web\http\payload\impl\FilePayload(n2n\io\managed\impl\FileFactory::createFromFs($path)));
			return;
		}
	}
}

hangar\Hangar::setup($appVarPath, $appAppPath, $appLibPath, $appPubPath, $appRootPath, 
		n2n\core\N2N::getCurrentRequest()->getCmdContextPath()->toUrl()->reducedPath(4)->extR(['public']));
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