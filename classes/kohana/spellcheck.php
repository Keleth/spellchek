<?php defined('SYSPATH') or die('No direct script access.');

/**
* Spellcheck Module
*/
class Kohana_Spellcheck {
    // Merged configuration settings
    protected $config = array(
		'lang' => 'ru',
		'dictionary' => '/var/tmp/kohana.pws',
                'regpattern' => '"/[\wа-яА-Я]+/u',
		);
	protected $misspeled = array();
	protected $pspell;
	protected $pspell_config;
	protected $pspell_personal_configured = false;

    /**
     * Creates new object Spellcheker
     * @param array $config
     * @return Spellchecker
     */
    public static function factory(array $config = array())
    {
        return new Spellcheck($config);
    }

    public function __construct(array $config = array())
    {
        // rewrite config
        $this->config = $this->config_group() + $this->config;
		$this->pspell_config = pspell_config_create($this->config['language']);
		if (pspell_config_personal($this->pspell_config, $this->config['dictionary']))
		{
			$this->pspell_personal_configured = true;
			$this->pspell = pspell_new_config($this->pspell_config);
		}
	}

   /**
    * Retrieves a spellcheck config group from the config file. One config group can
    * refer to another as its parent, which will be recursively loaded.
    *
    * @param  STRING spellcheck config group; "default" if none given
    * @return ARRAY  config settings
    */
    public function config_group($group = 'default')
    {
        // load config
        $config_file = Kohana::$config->load('spellcheck');
        // inititlize config
        $config['group'] = (string) $group;

        while(isset($config['group'])  AND isset($config_file->$config['group']))
        {
            // Temporarily store config group name
            $group = $config['group'];
            unset($config['group']);

            // Add config group values, not overwriting existing keys
            $config += $config_file->$group;
        }
        // Get rid of possible stray config group names
        unset($config['group']);

        // Return the merged config group settings
        return $config;
    }

	/**
	 * Checking text
	 * @param string $phrase
         * @return bool
	 */
	public function check($phrase)
	{
		if (!$this->pspell_personal_configured)
		{
			return false;
		}
		$tmp = array();
		if (preg_match_all($this->config['regpattern'], $phrase, $tmp))
		{
			foreach (Arr::get($tmp, 0) as $word)
			{
				$arr = array();
				if (!pspell_check($this->pspell, $word))
				{
					$suggestions = pspell_suggest($this->pspell, $word);
					$arr = array ($word => $suggestions);
					$this->misspeled += $arr;
				}
			}
		}
		if (!empty($this->misspeled))
		{
			return false;
		}
		else
		{
			return true;
		}
	}

	/**
	 * Add word to custom dictionary
	 * @param string $word - word for adding into dictionary
	 * @return bool
	 */
	public function add_to_personal_dictionary($word)
	{
		if (!$this->pspell_personal_configured)
		{
			return false;
		}
		if (pspell_add_to_personal($this->pspell, $word))
		{
			if (pspell_save_wordlist($this->pspell))
			{
                            return true;
			}
			return false;
		}
		return false;
	}

	/**
	 * Return array of words and its suggestions
	 * @return array
	 */
	public function misspelled()
	{
            return $this->misspeled;
	}
}


