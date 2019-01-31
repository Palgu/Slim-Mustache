<?php
/**
 * Slim Mustache - a Mustache view class for Slim
 *
 * @author      Remco Meeuwissen
 * @link        http://github.com/dearon/Slim-Mustache
 * @copyright   2014 Remco Meeuwissen
 * @version     0.1.0
 * @package     SlimMustache
 *
 * MIT LICENSE
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 * LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */
namespace Slim\Mustache;

/**
 * Mustache view
 *
 * The Mustache view is a custom View class that renders templates using the Mustache
 * template language (https://github.com/bobthecow/mustache.php).
 *
 * Two fields that you, the developer, will need to change are:
 * - parserDirectory
 * - parserOptions
 */
class Mustache extends \Slim\View
{
    /**
     * @var string The path to the Mustache code directory WITHOUT the trailing slash
     */
    public static $parserDirectory = null;
	public static $cacheDirectory = null;
	public static $ioDomain = null;
	public static $cssversion = null;
	public static $jsversion = null;
	public static $imgversion = null;
	public static $locale = null;

    /**
     * @var array The options for the Mustache engine, see
     * https://github.com/bobthecow/mustache.php/wiki
     */
    public $parserOptions = array();

    /**
     * @var Mustache_Engine The Mustache engine for rendering templates.
     */
    private $parserInstance = null;

    /**
     * Render Mustache Template
     *
     * This method will output the rendered template content
     *
     * @param   string $template The path to the Mustache template, relative to the templates directory.
     * @param null $data
     * @return  void
     */
    public function render($template, $data = null)
    {
        $env = $this->getInstance();
        $parser = $env->loadTemplate($template);

        return $parser->render($this->all());
    }

    /**
     * Creates new Mustache_Engine if it doesn't already exist, and returns it.
     *
     * @return \Mustache_Engine
     */
    public function getInstance()
    {
        if (!$this->parserInstance) {
            /**
             * Check if Mustache_Autoloader class exists
             * otherwise include and register it.
             */
            if (!class_exists('\Mustache_Autoloader')) {
                require_once $this->parserDirectory . '/Autoloader.php';
                \Mustache_Autoloader::register();
            }

            $parserOptions = array(
                'loader' => new \Mustache_Loader_FilesystemLoader($this->getTemplatesDirectory()),
				'cache' =>self:: $cacheDirectory,
				'show_empty'=>1,
				'cache_file_mode' => 0666, // Please, configure your umask instead of doing this :)
				'cache_lambda_templates' => true,
				'helpers' => array(
        			'dlang' => function($text, $mustache) {
	        			return "{{".$mustache->render($text)."}}";
	        		},'dpartial' => function($text, $mustache) {
						return "{{>".$mustache->render($text)."}}";
					},'lower' => function($text, $mustache){
						return strtolower ($mustache->render($text));
					},'upper'=> function($text, $mustache){
						return strtoupper ($mustache->render($text));
					},'cut_text'=>function($text, $mustache){
						$max_length = 230;//default
						$search = strtr($text, array('{' => '', '}' => ''));
						$pieces = explode("|", $search);
						$newt=$pieces[0];
						$max_length=$pieces[1];

						$text2=strip_tags($mustache->render('{{{'.$newt.'}}}'),'<b><strong><a>');
						if (strlen($text2) > $max_length){
						    $offset = ($max_length - 3) - strlen($text2);
						    $s = substr($text2, 0, strrpos($text2, ' ', $offset)) . '...';
						}else{
							$s=$text2;
						}
						return $s;
					},'anchor_url'=>function($text, $mustache){
						if(!$text)
							return "";
						return  substr($mustache->render($text), 1);
					},'ucw'=>function($text, $mustache){
						return ucwords (strtolower ($mustache->render($text)));	
					},'user_img'=>function($text, $mustache){
						if(substr($mustache->render($text),0,4)=='http')
						return $mustache->render($text);
						else
						return 	$mustache->render("/".$text);
					},'date_it' =>function($text, $mustache){
                        if(!$mustache->render($text))
                            return "";
						$date=date('d/m/Y', strtotime($mustache->render($text)));
						return $mustache->render($date);
					},'date_ext' =>function($text, $mustache){
                        if(!$mustache->render($text))
                            return "";
                        setlocale(LC_ALL, Mustache::$locale);
                        $date=strftime('%A %d %B %Y',strtotime($mustache->render($text)));
						return $mustache->render($date);
					},
					'date_year' =>function($text, $mustache){
                        if(!$mustache->render($text))
                            return "";
                        setlocale(LC_ALL, Mustache::$locale);
                        $date=strftime('%Y',strtotime($mustache->render($text)));
						return $mustache->render($date);
					},
					'date_month_name' =>function($text, $mustache){
                        if(!$mustache->render($text))
                            return "";
                        setlocale(LC_ALL, Mustache::$locale);
                        $date=strftime('%B',strtotime($mustache->render($text)));
						return $mustache->render($date);
					},
					'date_day_name' =>function($text, $mustache){
                        if(!$mustache->render($text))
                            return "";
                        setlocale(LC_ALL, Mustache::$locale);
                        $date=strftime('%A',strtotime($mustache->render($text)));
						return $mustache->render($date);
					},
					'date_day' =>function($text, $mustache){
                        if(!$mustache->render($text))
                            return "";
                        setlocale(LC_ALL, Mustache::$locale);
                        $date=strftime('%d',strtotime($mustache->render($text)));
						return $mustache->render($date);
					},
					'clean' => function($text, $mustache) {
						$output = iconv("utf-8", "ascii//TRANSLIT//IGNORE", $mustache->render($text)); 
						//lets remove utf-8 special characters except blank spaces
						$output = preg_replace("/^'|[^A-Za-z0-9\s-]|'$/", '', $output);
						$output = str_replace(' ', '-', $output);
						// clean up multiple '-' characters
						$output = preg_replace('/-{2,}/', '-', $output); 
						// remove trailing '-' character if string not just '-'
						if ($output != '-')
						$output = rtrim($output, '-');
						return strtolower (htmlspecialchars($output));
					},'file_name'=>function($text, $mustache){
						if(file_exists($mustache->render($text))){
						$path_parts = pathinfo($mustache->render($text));
						$filename=$path_parts['basename'];
						return $filename;
						}else{
						return "";
						}
					},'file_to_route'=>function($text, $mustache){
						if(file_exists($mustache->render($text))){
						$path_parts = pathinfo($mustache->render($text));
						$dirname=$path_parts['dirname'];
						$basepath=$path_parts['basename'];
						$filename=$path_parts['filename'];
						$extension=$path_parts['extension'];
						return $dirname."/".$filename."/".$extension;
						}else{
						return "";
						}
					},'tumb'=>function($text, $mustache){
						if(file_exists($mustache->render($text))){
						$path_parts = pathinfo($mustache->render($text));
						$dirname=$path_parts['dirname'];
						$basepath=$path_parts['basename'];
						$filename=$path_parts['filename'];
						$extension=$path_parts['extension'];
						return $dirname."/thumbnails/".$filename.".".$extension;
						}else{
						return "";
						}
					},'smart'=>function($text, $mustache){
						if(file_exists($mustache->render($text))){
						$path_parts = pathinfo($mustache->render($text));
						$dirname=$path_parts['dirname'];
						$basepath=$path_parts['basename'];
						$filename=$path_parts['filename'];
						$extension=$path_parts['extension'];
						return $dirname."/smart/".$filename.".".$extension;
						}else{
						return "";
						}
					},'bksrc'=>function($text, $mustache){
						if(file_exists("./".$mustache->render($text))){
						return "/".$mustache->render($text);
						}else{
						return "http://".Mustache::$ioDomain."/".$mustache->render($text);
						}
					},'cssv'=>function($text, $mustache){
						return $mustache->render($text)."?v=".Mustache::$cssversion;
					},'jsv'=>function($text, $mustache){
						return $mustache->render($text)."?v=".Mustache::$jsversion;
					},'imgv'=>function($text, $mustache){
						return $mustache->render($text)."?v=".Mustache::$imgversion;
					},'slugify'=>function($text, $mustache){
						return makeSlugs($mustache->render($text), $maxlen=0);
					}			
	        	)

            );

            // Check if the partials directory exists, otherwise Mustache will throw a exception
            if (is_dir($this->getTemplatesDirectory().'/partials')) {
                $parserOptions['partials_loader'] = new \Mustache_Loader_FilesystemLoader($this->getTemplatesDirectory().'/partials');
            }

            $parserOptions = array_merge((array)$parserOptions, (array)$this->parserOptions);

            $this->parserInstance = new \Mustache_Engine($parserOptions);
        }
        return $this->parserInstance;
    }
}
