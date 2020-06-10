<?php

class AS_UPDATER
{

	public $REMOTE_SSH;
	//const DEPLOYMENT_KEY = '';
	public $TARGET_DIR;
	public $TARGET_NAME;
	public $BRANCH;
	public $slug;
	public $has_update = false;
	public $name;
	public $ERROR;

	public $githubURI;
	public $owner;
	public $repo;
	public $last_version;


	public function __construct($TARGET_DIR)
	{
		$this->TARGET_DIR = $TARGET_DIR;

		add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
		add_action('admin_init', array($this, 'onAdminInit'));
//		add_action('wp_ajax_plugin_update', [$this, 'pull']);
		add_action('wp_ajax_plugin_update', function () {die($this->pull());});
	}

	function enqueue_scripts($hook)
	{
		if ('plugins.php' == $hook) {
			wp_enqueue_script('updater_script', plugins_url('../js/script.js', __FILE__), array('jquery'), '1.0.7');
			wp_enqueue_style('updater_style', plugins_url('../css/style.css', __FILE__));
		}
	}

	/**
	 * checkRemote - проверяет наличие обновлений.
	 * Возвращает true при наличии и false при отсутствии
	 * @return bool
	 */
	public function checkRemote(){

	    $this->githubURI = get_file_data(__DIR__ . '/../best2pay-woocommerce.php', ['Github' => 'Github URI'])['Github'];
	    $this->owner = explode('/', str_ireplace('https://', '', $this->githubURI))[1];
	    $this->repo = explode('/', str_ireplace('https://', '', $this->githubURI))[2];

	    $checking = json_decode(curl_get_contents('https://api.github.com/repos/' . $this->owner . '/' . $this->repo ));

	    if ($checking->message) {
            $this->ERROR = "Репозиторий не найден";
            return false;
        }

        $current_version = get_plugin_data(__DIR__ . '/../best2pay-woocommerce.php' )['Version'];

	    $last_version = json_decode(curl_get_contents('https://api.github.com/repos/' . $this->owner . '/' . $this->repo . '/releases/latest' ));

	    $this->last_version = $last_version;

	    if (!$last_version->name) {
            $this->ERROR = "Обновления не доступны";
            return false;
        }

		if ($last_version->name !== $current_version) {
			$this->has_update = true;
		} else {
			$this->has_update = false;
		}
		return ($this->has_update) ? $last_version->name : false;
	}


	public function pull(){
        $this->name = $_POST['namePluginForUpdate'];
		if (!$this->name) return false;
        $this->TARGET_DIR = str_ireplace(["/", "\\"], DIRECTORY_SEPARATOR, $this->TARGET_DIR . DIRECTORY_SEPARATOR);
		if ( $this->checkRemote() ) {
			$this->getArchive();
			$this->extractFiles();
			return true;
		}
		return false;
	}

	public function getArchive() {
	    $command = "cd $this->TARGET_DIR.. && wget " . $this->githubURI . '/archive/' . $this->last_version->tag_name . '.tar.gz';
        exec($command);
        return $this->TARGET_DIR;
	}

	public function extractFiles() {
        exec("cd $this->TARGET_DIR.. && " . 'tar xvfz ' . $this->last_version->tag_name . '.tar.gz && rm ' . $this->last_version->tag_name . '.tar.gz');
        return $this->TARGET_DIR;
    }

	public function onAdminInit() {
			add_filter('plugin_row_meta', array($this, 'addCheckForUpdatesLink'), 10, 2);
	}

	public function addCheckForUpdatesLink($pluginMeta, $pluginFile) {
		$file_name_1 = explode(DIRECTORY_SEPARATOR, str_ireplace(['/', '\\'], DIRECTORY_SEPARATOR, $pluginFile))[0] . '<br>';
		$file_name_2 = explode(DIRECTORY_SEPARATOR, str_ireplace(['/', '\\'], DIRECTORY_SEPARATOR, $this->TARGET_DIR));
		$file_name_2 = $file_name_2[count($file_name_2)-1];
		if ( false === strpos($file_name_1 , $file_name_2) ) return $pluginMeta;

			$linkText = ($ver = $this->checkRemote()) ? "Обновить до версии $ver" : 'У вас актуальная версия';
			if ( !empty($linkText) ) {
				/** @noinspection HtmlUnknownTarget */
				$pluginMeta[] = sprintf('<a id="updatePlugin" class="%s" name="%s" style="%s"><strong>%s</strong></a>',
					(!$this->ERROR) ? 'ok' : '',
					$pluginFile,
					$style = ($this->has_update) ? 'color:#07c907' : (($this->ERROR) ? 'color:#ff0202' : '') ,
					$text = ($this->ERROR) ? $this->ERROR : $linkText);
			}
			return $pluginMeta;
	}

	public function removeHooks() {
		remove_action('admin_init', array($this, 'onAdminInit'));
		remove_filter('plugin_row_meta', array($this, 'addCheckForUpdatesLink'), 10);
	}

}

function curl_get_contents($url)
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_USERAGENT, 'curl');
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}
