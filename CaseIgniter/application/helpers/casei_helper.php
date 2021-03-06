<?php

class Attribute
{

    public $name;

    public $type;

    public $collection;

    public $mode;

    public $hidden_create;

    public $hidden_recover;

    public $main;

    public $unique;
    
    public $notnull;
    
    public function __construct($name, $type, $collection, $mode, $hidden_create = false, $hidden_recover = false, $main = false, $unique = false, $notnull=false)
    {
        $this->name = $name;
        $this->type = $type;
        $this->collection = $collection;
        $this->mode = $mode;
        $this->hidden_create = $hidden_create;
        $this->hidden_recover = $hidden_recover;
        $this->main = $main;
        $this->unique = $unique;
        $this->notnull= $notnull;
    }

    public function is_dependant()
    {
        $m = $this->mode;
        return $m == 'O2O' || $m == 'M2M' || $m == 'M2Mi' || $m == 'M2O' || $m == 'O2M';
    }
}

// ------------------------------
class MyClass
{

    public $name;

    public $attributes = [];

    public $usecases = [];

    public $c = [];

    public $r = [];

    public $u = [];

    public $d = [];

    public $login_bean = false;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function add_attribute($attribute)
    {
        array_push($this->attributes, $attribute);
    }

    public function add_main_attribute($attribute)
    {
        array_unshift($this->attributes, $attribute);
    }

    public function has_collections()
    {
        $answer = false;
        foreach ($this->attributes as $a) {
            $answer |= $a->collection;
        }
        return $answer;
    }

    public function has_images()
    {
        $answer = false;
        foreach ($this->attributes as $a) {
            $answer |= ($a->type == 'file');
        }
        return $answer;
    }

    public function has_dependants()
    {
        $answer = false;
        foreach ($this->attributes as $a) {
            $answer |= $a->is_dependant();
        }
        return $answer;
    }

    public function setMainAttribute()
    {
        $exist_main = false;
        foreach ($this->attributes as $a) {
            $exist_main |= ($a->main);
        }
        if (! $exist_main) {
            $this->add_main_attribute(new Attribute("nombre", "String", false, "NO_MODE", false, false, true)); // TODO LOC
        }
    }

    public function getMainAttribute()
    {
        $name = 'nombre'; // TODO LOC
        foreach ($this->attributes as $a) {
            if ($a->main) {
                $name = $a->name;
            }
        }
        return $name;
    }
}

// ===================================================================
// ===================================================================
// ===================================================================

// ===================================================================
function plural($word)
{
    $last_char = substr($word, - 1, 1);
    $vowels = [
        'a',
        'e',
        'i',
        'o',
        'u'
    ];
    return $word . (in_array($last_char, $vowels) ? 's' : 'es');
}

// ---------------------------------------------
function classes_have_login_bean($classes)
{
    $response = false;
    foreach ($classes as $class) {
        $response |= $class->login_bean;
    }
    return $response;
}

// ---------------------------------------------
function create_rol_class($login_bean_name)
{
    $rol_class = new MyClass('rol');
    $rol_class->attributes[] = new Attribute('nombre', 'String', false, 'NO_MODE', false, false, true);
    $rol_class->attributes[] = new Attribute('descripcion', 'String', false, 'NO_MODE', false, false, false);
    $rol_class->attributes[] = new Attribute('roles', $login_bean_name, true, 'M2M', false, false, false);
    $rol_class->attributes[] = new Attribute('rolrequest', $login_bean_name, true, 'M2M', false, false, false);

    return $rol_class;
}

// ---------------------------------------------
function generate_admin($classes)
{
    $login_bean = null;
    foreach ($classes as $c) {
        if ($c->login_bean) {
            $login_bean = $c;
        }
    }

    // CREATE DEFAULT ROL IF NOT EXISTS
    if (R::findOne('rol', 'nombre=?', [
        'default'
    ]) == null) {
        $rol = R::dispense('rol');
        $rol->nombre = 'default';
        $rol->descripcion = 'Default user - SET YOURS';
        R::store($rol);
    }

    // CREATE ADMIN ROL IF NOT EXISTS
    $rol = null;
    $rol_id = 0;
    if (R::findOne('rol', 'nombre=?', [
        'admin'
    ]) == null) {
        $rol = R::dispense('rol');
        $rol->nombre = 'admin';
        $rol->descripcion = 'Administrador';
        $rol_id = R::store($rol);
    } else {
        $rol = R::findOne('rol', 'nombre = ?', [
            'admin'
        ]);
        $rol_id = $rol->id;
    }

    // CREATE ADMIN USER IF NOT EXISTS
    $ma = $login_bean->getMainAttribute();
    $cn = $login_bean->name;
    $admin = null;
    $admin_id = 0;
    if (R::findOne($cn, 'loginname = ?', [
        'admin'
    ]) == null) {
        $admin = R::dispense($cn);
        $admin->loginname = 'admin';
        $admin->password = password_hash('admin', PASSWORD_DEFAULT);
        $admin->$ma = 'Administrador';
        $admin_id = R::store($admin);
    } else {
        $admin = R::findOne($cn, 'loginname = ?', [
            'admin'
        ]);
        $admin_id = $admin->id;
    }

    // ASSIGN ADMIN ROL TO ADMIN USER IF NOT ASSIGNED YET
    try {
        if (R::findOne('roles', $cn . '_id = ? AND rol_id = ?', [
            $admin_id,
            $rol_id
        ]) == null) {
            $roles = R::dispense('roles');
            $roles->rol = $rol;
            $roles->$cn = $admin;
            $roles->rol = $rol;
            R::store($roles);
        }
    } catch (Exception $e) {}

    // GENERATING
}

// ---------------------------------------------
function set_login_bean_class(&$classes)
{
    $rol_class = null;
    if (classes_have_login_bean($classes)) {
        foreach ($classes as $c) {
            if ($c->login_bean) {
                $rol_class = create_rol_class($c->name);
                $c->attributes[] = new Attribute('loginname', 'String', false, 'NO_MODE', false, false, false, true, true);
                $c->attributes[] = new Attribute('password', 'String', false, 'NO_MODE', false, true, false);
                $c->attributes[] = new Attribute('roles', 'rol', true, 'M2M', true, false, false);
                $c->attributes[] = new Attribute('rolrequest', 'rol', true, 'M2M', true, false, false);
            }
        }
    }
    return $rol_class;
}

// ---------------------------------------------
function process_domain_model($modelData)
{

    // ----------------------------------

    /**
     *
     * @param string $line
     * @return $string BEAN_SEPARATOR, ATTRIBUTE_SEPARATOR, BEAN_NAME, ROL_LINE, ATTRIBUTE or UNKNOWN
     */
    function pdm_line_type($line)
    {
        $type = 'UNKNOWN';

        if (preg_match("/[=]+$/", $line)) {
            $type = 'BEAN_SEPARATOR';
        }
        if (preg_match("/[\.]+$/", $line)) {
            $type = 'ATTRIBUTE_SEPARATOR';
        }
        if (preg_match("/^[A-Z0-9]+(\s\[login\])?$/", $line)) {
            $type = 'BEAN_NAME';
        }
        if (preg_match("/^[\s]*([crud])[\s]*\(([a-z0-9]+[,[a-z0-9]+]*)\)[\s]*$/", $line)) {
            $type = 'ROL_LINE';
        }
        if (preg_match("/^((\*\*|\*>|<>|<\*)\s)?[a-z0-9]+(:([a-z]+|\@|\%|\#))?(\s\[[a-zA-Z\-]+(,[a-zA-Z\-]+)*\])?$/", $line)) {
            $type = 'ATTRIBUTE';
        }
        if (preg_match("/^\[([a-z]+[\,a-z]*)\]\s([a-z\_]+)\(\)$/", $line)) {
            $type = 'USE_CASE';
        }

        return $type;
    }

    // ----------------------------------
    function pdm_process_bean_name($line, $classes)
    {
        $class = new MyClass(strtolower(explode(' ', $line)[0]));
        if (strpos($line, '[login]')) {
            if (! classes_have_login_bean($classes)) {
                $class->login_bean = true;
            } else {
                throw (new Exception("ERROR while parsing model.txt: Only one LOGIN bean allowed"));
            }
        }
        return $class;
    }

    // =============================================================
    function pdm_process_attribute($line)
    {
        error_reporting(0);
        $name = 'NO_NAME';
        $type = 'String';
        $collection = false;
        $mode = 'NO_MODE';
        $hidden_create = false;
        $hidden_recover = false;
        $main = false;

        $pattern = "/^([\*\<\>]+\s)?([a-z0-9]+)(\:[a-z\%\@\#]+)?(\s\[[a-zA-Z,\-]+\])?$/";
        preg_match($pattern, $line, $matches);
        $multiplicity = $matches[1] != '' ? rtrim($matches[1]) : 'REGULAR';
        $name = $matches[2];
        $type = $matches[3] != '' ? ltrim($matches[3], "\s\:") : 'String';
        $modifiers = trim($matches[4], " []");

        $collection = ($multiplicity != 'REGULAR');

        switch ($multiplicity) {
            case '<>':
                $mode = 'O2O';
                break;
            case '*>':
                $mode = 'M2O';
                break;
            case '<*':
                $mode = 'O2M';
                break;
            case '**':
                $mode = 'M2M';
                break;
        }

        switch ($type) {
            case '#':
                $type = 'number';
                break;
            case '%':
                $type = 'date';
                break;
            case '@':
                $type = 'file';
                break;
        }

        $hidden_create = (strpos($modifiers, 'c-') !== false);
        $hidden_recover = (strpos($modifiers, 'r-') !== false);
        $main = (strpos($modifiers, 'M') !== false);
        $unique = (strpos($modifiers, 'U') !== false);
        if ($multiplicity != 'REGULAR' && $mode == 'UNIQUE') {
            throw new Exception("ERROR while parsing model.txt: Only REGULAR attributes can be UNIQUE <br/><b>$line</b>");
        }
        if ($mode == 'M2M' && strpos($name, '_') !== false) {
            throw new Exception("ERROR while parsing model.txt: <i>Many to many</i> attribute name cannot contain underscores <br/><b>$line</b>");
        }

        error_reporting(E_ALL);

        return new Attribute($name, $type, $collection, $mode, $hidden_create, $hidden_recover, $main, $unique);
    }

    // =============================================================
    function pdm_process_usecase($line)
    {
        $pattern = "/^\[([a-z]+[\,a-z]*)\]\s([a-z\_]+)\(\)$/";
        preg_match($pattern, $line, $matches);
        $roles = $matches[1];
        $usecase = $matches[2];
        return [
            $usecase => explode(',', $roles)
        ];
    }

    // =============================================================
    function pdm_process_rol_line($line, $current_class)
    {
        $pattern = "/^[\s]*([crud])[\s]*\(([a-z0-9]+[,[a-z0-9]+]*)\)[\s]*$/";
        preg_match($pattern, $line, $matches);
        $crud = $matches[1];
        $roles = $matches[2];
        $current_class->$crud = explode(',', $roles);
    }

    // =============================================================

    $lines = $modelData;
    $line_number = 0;
    $classes = [];
    $current_class = null;

    $state = 'idle';

    foreach (explode("\n", $lines) as $line) {
        $line_number ++;
        $line = trim($line);
        if ($line != "") {
            switch ($state) {
                case 'idle':
                    if (pdm_line_type($line) != 'BEAN_SEPARATOR') {
                        throw new Exception("ERROR while parsing model.txt (line $line_number): Bean separator expected <br/><b>$line</b>");
                    }
                    $state = 'bean_name';
                    break;
                case 'bean_name':
                    if (pdm_line_type($line) != 'BEAN_NAME') {
                        throw new Exception("ERROR while parsing model.txt (line $line_number): Bean name expected <br/><b>$line</b>");
                    }
                    $current_class = pdm_process_bean_name($line, $classes);
                    $state = 'rol_line';
                    break;
                case 'rol_line':
                    if (! (pdm_line_type($line) == 'ROL_LINE' || pdm_line_type($line) == 'ATTRIBUTE_SEPARATOR')) {
                        throw new Exception("ERROR while parsing model.txt (line $line_number): Rol line or attribute separator expected <br/><b>$line</b>");
                    }
                    if (pdm_line_type($line) == 'ROL_LINE') {
                        pdm_process_rol_line($line, $current_class);
                    } else { // ATTRIBUTE_SEPARATOR
                        $state = 'attribute';
                    }
                    break;
                case 'attribute':
                    if (! (pdm_line_type($line) == 'ATTRIBUTE' || pdm_line_type($line) == 'BEAN_SEPARATOR' || pdm_line_type($line) == 'ATTRIBUTE_SEPARATOR')) {
                        throw new Exception("ERROR while parsing model.txt (line $line_number): Attribute or bean separator expected <br/><b>$line</b>");
                    }
                    if (pdm_line_type($line) == 'BEAN_SEPARATOR') {
                        $current_class->setMainAttribute();
                        $classes[] = $current_class;
                        $current_class = null;
                        $state = 'idle';
                    } else if (pdm_line_type($line) == 'ATTRIBUTE_SEPARATOR') {
                        $current_class->setMainAttribute();
                        $current_class->usecases = [];
                        $state = 'usecases';
                    } else { // ATTRIBUTE
                        $current_class->add_attribute(pdm_process_attribute($line));
                    }
                    break;
                case 'usecases':
                    if (! (pdm_line_type($line) == 'USE_CASE' || pdm_line_type($line) == 'BEAN_SEPARATOR')) {
                        throw new Exception("ERROR while parsing model.txt (line $line_number): Use case or bean separator expected <br/><b>$line</b>");
                    }
                    if (pdm_line_type($line) == 'BEAN_SEPARATOR') {
                        $classes[] = $current_class;
                        $current_class = null;
                        $state = 'idle';
                    } else { // USECASE
                        $current_class->usecases[] = pdm_process_usecase($line);
                    }
                    break;
            }
        }
    }

    if ($state != 'idle') {
        throw new Exception("MODEL PARSE ERROR ($line_number): Unexpected end of file <br/><b>$line</b>");
    }

    return $classes;
}

// ------------------------------
function delete_directory($path, $ignore_files, $first_level)
{
    if (! file_exists($path)) { // Name not correct
        return true;
    }

    if (! is_dir($path)) { // It's a file
        if (! in_array(basename($path), $ignore_files)) {
            return unlink($path);
        } else {
            return true;
        }
    }

    foreach (scandir($path) as $item) { // It's a directory. Let's process its content
        if ($item == '.' || $item == '..') { // Ignore . and ..
            continue;
        }

        if (! in_array($item, $ignore_files) && ! delete_directory($path . DIRECTORY_SEPARATOR . $item, $ignore_files, false)) {
            return false;
        }
    }

    if (! $first_level) { // It's a directory of deeper levels than first
        if (! in_array(basename($path), $ignore_files)) {
            return rmdir($path);
        } else {
            return true;
        }
    } else { // It's the first level. We're done
        return true;
    }
}

// ------------------------------
function delete_attribute($classes, $class_name, $attribute_name)
{
    foreach ($classes as $class) {
        if ($class->name == $class_name) {
            foreach ($class->attributes as $i => $a) {
                if ($a->name == $attribute_name) {
                    unset($class->attributes[$i]);
                }
            }
        }
    }
}

// ------------------------------
function generate_yuml($classes)
{
    $colors = [
        'yellowgreen',
        'yellow',
        'wheat',
        'violet',
        'turquoise',
        'tomato',
        'thistle',
        'tan',
        'steelblue',
        'springgreen',
        'snow',
        'slategray',
        'slateblue',
        'skyblue',
        'sienna',
        'seashell',
        'seagreen',
        'sandybrown',
        'salmon',
        'saddlebrown',
        'royalblue',
        'rosybrown',
        'red',
        'purple',
        'powderblue',
        'plum',
        'pink',
        'peru',
        'peachpuff'
    ];
    $c = 0;
    $html = '<img src="http://yuml.me/diagram/dir:td;scale:150/class/';
    foreach ($classes as $class) {
        $class_name = $class->login_bean ? strtoupper($class->name) : ucfirst($class->name);
        $html .= '[' . $class_name;
        $html .= '|';
        $i = 0;
        foreach ($class->attributes as $a) {
            if (! is_dependant($a)) {
                $html .= ((($a->main) ? '** ' : '') . $a->name);
                $html .= ($a->type == 'String') ? '' : (':' . $a->type);
                $html .= ';';
                unset($class->attributes[$i]);
            }
            $i ++;
        }
        if ($class->usecases != []) {
            $html .= '|';
            foreach ($class->usecases as $u) {
                foreach ($u as $f => $r) {
                    $html .= "{$f}();";
                }
            }
        }
        $html .= ('{bg:' . $colors[($c ++)] . '}');
        $html .= '],';
    }

    foreach ($classes as $class) {
        $alt = true;
        foreach ($class->attributes as $i => $a) {
            $rel_name = ($alt ? ' ' : '') . $a->name . ($alt ? '' : ' ');
            $alt = ! $alt;
            switch ($a->mode) {
                case 'M2O':
                    $html .= '[' . ($class->name) . ']- ' . $rel_name . '  <>[' . ($a->type) . '],';
                    break;
                case 'O2M':
                    $html .= '[' . ($class->name) . ']<>  ' . $rel_name . '-[' . ($a->type) . '],';
                    break;
                case 'O2O':
                    $html .= '[' . ($class->name) . ']<>- ' . $rel_name . '  <>[' . ($a->type) . '],';
                    break;
                case 'M2M':
                    $html .= '[' . ($class->name) . ']- ' . $rel_name . '  [' . ($a->type) . '],';
                    break;
            }
            unset($class->attributes[$i]);
            delete_attribute($classes, $a->type, $a->name);
        }
    }
    return $html . '">';
}

// ------------------------------
function get_login_bean($classes)
{
    $login_bean = null;
    foreach ($classes as $c) {
        if ($c->login_bean) {
            $login_bean = $c;
        }
    }
    return $login_bean;
}

// ------------------------------
function generate_home_controller($login_bean)
{
    $lb = ($login_bean == null ? '' : "\t\t\$_SESSION['login_bean'] = '{$login_bean->name}';");
    $code = <<<CODE
<?php
class _home extends CI_Controller {

	public function index() {
		if (session_status () == PHP_SESSION_NONE) {session_start ();}
		$lb
		frame(\$this, '_home/index');
	}

    public function info() {
        if ( session_status () == PHP_SESSION_NONE) { session_start (); }
        if ( !isset(\$_SESSION['info']['status']) || !isset(\$_SESSION['info']['message']) ) {
            \$_SESSION['info']['status'] = 'info';
            \$_SESSION['info']['message'] = 'Pulsa el botón para volver a home';
            \$_SESSION['info']['link'] = null;
        }

        \$data['status'] = \$_SESSION['info']['status'];
        \$data['message'] = \$_SESSION['info']['message'];
        \$data['link'] = isset(\$_SESSION['info']['link']) ? \$_SESSION['info']['link'] : null;

        \$_SESSION['info']['status'] = 'info';
        \$_SESSION['info']['message'] = 'Pulsa el botón para volver a home';
        \$_SESSION['info']['link'] = null;

        frame(\$this,'_home/_info',\$data);
    }
}
?>
CODE;

    file_put_contents(APPPATH . 'controllers' . DIRECTORY_SEPARATOR . '_home.php', $code);
}

// ------------------------------
function delete_directories($classes)
{
    $ignore_files = [
        '_casei.php',
        '_casei',
        '_home.php',
        '_home',
        'errors',
        '_templates',
        'index.html'
    ];

    foreach ($classes as $class) {
        $ignore_files[] = $class->name;
        $ignore_files[] = $class->name . '.php';
        $ignore_files[] = $class->name . '_model.php';
    }

    delete_directory(APPPATH . 'controllers', $ignore_files, true);
    delete_directory(APPPATH . 'models', $ignore_files, true);
    delete_directory(APPPATH . 'views', $ignore_files, true);
}

// ------------------------------
function change_title($title)
{
    $d = DIRECTORY_SEPARATOR;
    $head_file = APPPATH . "views{$d}_templates{$d}head.php";
    $html = file_get_contents($head_file);
    $pattern = '/<\?php \$title="(.)*"; \?>/';
    $replacement = '<?php \$title="' . $title . '"; ?>';

    file_put_contents($head_file, preg_replace($pattern, $replacement, $html));
}

// ------------------------------
function generate_menus($menuData, $appTitle, $classes)
{

    function generate_menus_process_line($line, $classes)
    {
        $roles = '\[[a-z0-9,]+\]';
        $menu = '[A-Za-z0-9]+';
        $uri = '[A-Za-z0-9\-\/]+';
        $submenu = $menu . '\(' . $uri . '\)';
        $submenus = '(' . $submenu . '(\,' . $submenu . ')*)';
        $pattern = "/^[\s]*" . '(' . $roles . ')?' . "[\s]*" . '(' . $menu . ')' . '>' . $submenus . "[\s]*$/";

        $nav = '';
        if (preg_match($pattern, $line, $m)) {
            if (isset($m[1])) { // Process ROL
                $if_cond = [];
                foreach (explode(',', trim($m[1], '[]')) as $rol) {
                    $if_cond[] = "\$nav['rol']->nombre == '$rol'";
                }
                $if_cond = implode(' || ', $if_cond);
                $if_cond = '( ' . $if_cond . ' )';
                $nav .= <<<NAV

			<?php if (isset (\$nav['rol']) && $if_cond ): ?> 

NAV;
            }
            if (isset($m[2])) { // Process MENU
                $nav .= <<<NAV

			<li class="nav-item dropdown">
				<a class="nav-link dropdown-toggle" data-toggle="dropdown" href="#">
					$m[2]
				</a>

				<div class="dropdown-menu">

NAV;
            }
            if (isset($m[3])) { // Process SUBMENUS
                foreach (explode(',', $m[3]) as $submenu) {
                    preg_match('/([0-9a-zA-Z]+)\(([0-9a-zA-Z\/\-\_]+)\)/', $submenu, $match);
                    $nav .= <<<NAV

					<a class="dropdown-item" href="<?=base_url()?>$match[2]">$match[1]</a>

NAV;
                }
            }
            $nav .= <<<NAV

				</div>
			</li>		

NAV;

            if (isset($m[1])) { // Process ROL
                $nav .= <<<NAV
				
			<?php endif; ?>
			
NAV;
            }
        }
        return $nav;
    }

    // -----------------------------------------------------------------------------
    // ------------------- generate_menus() BEGGINING ------------------------------
    // -----------------------------------------------------------------------------

    $nav = <<<NAV
<nav class="container navbar navbar-expand-sm bg-dark navbar-dark rounded">

	<a class="navbar-brand" href="<?=base_url()?>">
		<img src="<?=base_url()?>assets/img/icons/png/home-alt.png" alt="INICIO" style="width:40px;">
	</a>

	<ul class="navbar-nav">

NAV;

    $nav .= generate_menus_CRUD($classes);
    $nav .= generate_menus_CSI($classes);

    foreach (explode("\n", $menuData) as $line) {
        if (trim($line) != '') {
            $nav .= generate_menus_process_line($line, $classes);
        }
    }
    $nav .= <<<NAV
 
   </ul>
</nav>
NAV;
    return $nav;
}

// ------------------------------
function generate_menus_CSI($classes)
{
    $if_begin = classes_have_login_bean($classes) ? "<?php if (isset(\$nav['rol']) && \$nav['rol']->nombre == 'admin'): ?>" : '';
    $if_end = classes_have_login_bean($classes) ? "<?php endif; ?>" : '';

    $csi = <<<NAV
	

		$if_begin
			<li class="nav-item">
				<a class="nav-link" href="<?=base_url()?>_casei">
					CSI
				</a>
			</li>
		$if_end
		
NAV;
    return $csi;
}

// ------------------------------
function generate_menus_USECASE($classes, $usecase)
{
    /*
     * foreach ($usecase as $rol => $action) {
     *
     * $if_begin = classes_have_login_bean ( $classes ) ? "<?php if (isset(\$nav['rol']) && \$nav['rol']->nombre == 'admin'): ?>" : '';
     * $if_end = classes_have_login_bean ( $classes ) ? "<?php endif; ?>" : '';
     *
     *
     * $crud = <<<USECASE
     *
     * $if_begin
     * <li class="nav-item dropdown">
     * <a class="nav-link dropdown-toggle" data-toggle="dropdown" href="#">
     * BEANS
     * </a>
     *
     * <div class="dropdown-menu">
     *
     * USECASE;
     *
     * foreach ( $classes as $class ) {
     * $n = $class->name;
     * $crud .= <<<USECASE
     *
     * <a class="dropdown-item" href="<?=base_url()?>$n/list">$n</a>
     *
     * USECASE;
     * }
     * $crud .= <<<USECASE
     * </div>
     *
     * </li>
     *
     * $if_end
     * USECASE;
     *
     *
     * return $crud;
     *
     * }
     */
}

// ------------------------------
function generate_menus_CRUD($classes)
{
    $if_begin = classes_have_login_bean($classes) ? "<?php if (isset(\$nav['rol']) && \$nav['rol']->nombre == 'admin'): ?>" : '';
    $if_end = classes_have_login_bean($classes) ? "<?php endif; ?>" : '';

    $crud = <<<NAV

		$if_begin
			<li class="nav-item dropdown">
				<a class="nav-link dropdown-toggle" data-toggle="dropdown" href="#">
					BEANS 
				</a>
	
				<div class="dropdown-menu">

NAV;

    foreach ($classes as $class) {
        $n = $class->name;
        $crud .= <<<NAV

				<a class="dropdown-item" href="<?=base_url()?>$n/list">$n</a>

NAV;
    }
    $crud .= <<<NAV
			</div>

		</li>
		
		$if_end
NAV;
    return $crud;
}

// ------------------------------
function is_dependant($attribute)
{
    $t = $attribute->mode;
    return $t == "M2M" || $t == "M2Mi" || $t == "M2O" || $t == "O2M" || $t == "O2O";
}

// ------------------------------
function generate_application_files(&$classes)
{
    delete_directories($classes);
    generate_controllers($classes);
    generate_models($classes);
    generate_views($classes);
}

// ------------------------------
function generate_controllers($classes)
{
    foreach ($classes as $class) {
        generate_controller($class, $classes);
    }
}

// ------------------------------
function generate_models($classes)
{
    foreach ($classes as $class) {
        generate_model($class, $classes);
    }
}

// ------------------------------
function generate_views(&$classes)
{
    foreach ($classes as $class) {
        generate_view($class, $classes);
    }
}

// ------------------------------
function generate_frame_helper($login_bean)
{
    $login_bean_assign = ($login_bean != null ? "\$data ['header'] ['login_bean'] = '$login_bean';" : '');
    $code = <<<CODE
<?php
function frame(\$controller, \$path_to_view, \$data = []) {
	if (session_status () == PHP_SESSION_NONE) {
		session_start ();
	}
	if (isset ( \$_SESSION ['user'] ) && isset ( \$_SESSION ['rol'] )) {
		\$data ['header'] ['user'] = \$_SESSION ['user'];
		\$data ['header'] ['rol'] = \$_SESSION ['rol'];
		\$data ['nav'] ['rol'] = \$_SESSION ['rol'];
	}
	$login_bean_assign
	\$controller->load->view ( '_templates/head', \$data );
	\$controller->load->view ( '_templates/header', \$data );
	\$controller->load->view ( '_templates/nav', \$data );
	\$controller->load->view ( \$path_to_view, \$data );
	\$controller->load->view ( '_templates/footer', \$data );
	\$controller->load->view ( '_templates/end' );
}
?>
CODE;

    file_put_contents(APPPATH . 'helpers' . DIRECTORY_SEPARATOR . 'frame_helper.php', $code);
}

// ------------------------------
function get_jquery_ajax_code($uri_post)
{
    $uri_post = base_url() . $uri_post;
    return <<<CODEJQ
	
		<script type="text/javascript">
		$(document).ready(function(){
	 		$("#id-form").submit(function(e){
                e.preventDefault();
                $('#id-modal').on('shown.bs.modal', function() {
                	  $(this).find('[autofocus]').focus();
                });
                $.ajax({
                    url: '$uri_post',
                    type: 'POST',
                    data: $("#id-form").serialize(),
                    dataType:"json",
                    success: function(data){
                     	$("#id-modal-message").html(data.message);
                     	if (data.severity == "ERROR" ) {
                         	$("#id-modal-header").html("ERROR");
                     		$("#id-modal-message").attr('class', 'modal-title text-center bg-danger');
            				$("#id-modal").modal('show');
                 	}
                     	else if (data.severity == "WARNING" ) {
                         	$("#id-modal-header").html("Atención");
                     		$("#id-modal-message").attr('class', 'modal-title text-center bg-warning');
	        				$("#id-modal").modal('show');
                     	}
                     	else { //SUCCESS
                     		$(location).attr("href", data.message);

                         }
			
	              	}
              	})
            });
        });
			
		</script>
CODEJQ;
}

// ------------------------------
function get_modal_code()
{
    return <<<CODE

<div class="modal fade" id="id-modal" role="dialog">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header text-center" id="id-modal-header">ERROR</div>
			<h4 class="modal-title text-center bg-success" id="id-modal-message">SIN AJAX</h4>
			<div class="modal-footer">
				<button type="button" class="btn " data-dismiss="modal" id="id-modal-button" autofocus="autofocus">Aceptar</button>
			</div>
		</div>
	</div>
</div>

CODE;
}

// ------------------------------
function backup_and_save($filename, $code)
{
    if (file_exists($filename)) {
        file_put_contents(substr($filename, 0, - 4) . '_bak.php', file_get_contents($filename));
    }
    file_put_contents($filename, $code);
}

// ------------------------------
function generate_controller_list_change_rol_requests($class)
{
    $code = <<<CODE

	/**
	 * Controller action LIST CHANGE ROL REQUESTS for controller $class->name
	 * autogenerated by CASE IGNITER
	 */
	public function listChangeRolRequests() {
	    
	    
	    // =============================================
	    // ROLE CHECKING START
	    // =============================================
	    
	    if (session_status () == PHP_SESSION_NONE) {session_start ();}
	    \$rol_ok = false;
	    if ( isset(\$_SESSION['rol'] ) && \$_SESSION['rol']->nombre=='admin' ) { \$rol_ok=true; }
	    if ( !\$rol_ok ) { show_404(); }
	    
	    // =============================================
	    // ROLE CHECKING END
	    // =============================================
	    
	    \$this->load->model('{$class->name}_model');
	    \$data['body']['rolrequests'] = \$this->{$class->name}_model->list_change_rol_requests();
        frame(\$this, '{$class->name}/listChangeRolRequests', \$data );
        
	}
	


CODE;
    return $code;
}

// ------------------------------
function generate_controller_accept_rol_change_post($class)
{
    $code = <<<CODE

	/**
	 * Controller action ACCPET ROL CHANGE POST for controller $class->name
	 * autogenerated by CASE IGNITER
	 */
	public function acceptRC() {
	    
	    
	    // =============================================
	    // ROLE CHECKING START
	    // =============================================
	    
	    if (session_status () == PHP_SESSION_NONE) {session_start ();}
	    \$rol_ok = false;
	    if ( isset(\$_SESSION['rol'] ) && \$_SESSION['rol']->nombre=='admin' ) { \$rol_ok=true; }
	    if ( !\$rol_ok ) { show_404(); }
	    
	    // =============================================
	    // ROLE CHECKING END
	    // =============================================
	    
	    \$this -> load -> model('{$class->name}_model');
        \$id = isset(\$_POST['id'])?\$_POST['id']:null;
        \$this -> {$class->name}_model -> process_change_rol(\$id,'accept');
        redirect(base_url().'{$class->name}/listChangeRolRequests');
        
	}


CODE;
    return $code;
}

// ------------------------------
function generate_controller_reject_rol_change_post($class)
{
    $code = <<<CODE

	/**
	 * Controller action REJECT ROL CHANGE POST for controller $class->name
	 * autogenerated by CASE IGNITER
	 */
	public function rejectRC() {
	    
	    
	    // =============================================
	    // ROLE CHECKING START
	    // =============================================
	    
	    if (session_status () == PHP_SESSION_NONE) {session_start ();}
	    \$rol_ok = false;
	    if ( isset(\$_SESSION['rol'] ) && \$_SESSION['rol']->nombre=='admin' ) { \$rol_ok=true; }
	    if ( !\$rol_ok ) { show_404(); }
	    
	    // =============================================
	    // ROLE CHECKING END
	    // =============================================
	    
	    \$this -> load -> model('{$class->name}_model');
        \$id = isset(\$_POST['id'])?\$_POST['id']:null;
        \$this -> {$class->name}_model -> process_change_rol(\$id,'reject');
        redirect(base_url().'{$class->name}/listChangeRolRequests');
        
	}


CODE;
    return $code;
}

// ------------------------------
function generate_controller($class, $classes)
{
    $code = '';
    $code .= generate_controller_header($class->name);
    $code .= generate_controller_create($class, $classes);
    $code .= generate_controller_create_post($class, $classes);
    $code .= generate_controller_delete($class, $classes);
    $code .= generate_controller_list($class, $classes);
    $code .= generate_controller_list_id($class);
    $code .= generate_controller_update($class, $classes);
    $code .= generate_controller_update_post($class, $classes);

    if ($class->login_bean) {
        $code .= generate_controller_change_password($class);
        $code .= generate_controller_change_password_post($class);
        $code .= generate_controller_change_rol($class);
        $code .= generate_controller_change_rol_post($class);
        $code .= generate_controller_request_change_rol($class);
        $code .= generate_controller_request_change_rol_post($class);
        $code .= generate_controller_list_change_rol_requests($class);

        $code .= generate_controller_accept_rol_change_post($class);
        $code .= generate_controller_reject_rol_change_post($class);

        $code .= generate_controller_login($class);
        $code .= generate_controller_login_post($class);
        $code .= generate_controller_logout($class);
    }
    if ($class->usecases != []) {
        foreach ($class->usecases as $usecase) {
            foreach ($usecase as $usecase_name => $roles) {
                $code .= generate_controller_additional_usecase($class, $usecase_name, $roles, $classes);
            }
        }
    }
    $code .= generate_controller_end();
    $filename = APPPATH . 'controllers' . DIRECTORY_SEPARATOR . $class->name . '.php';
    backup_and_save($filename, $code);
}

// ------------------------------
function generate_controller_request_change_rol_post($class)
{
    $code = <<<CODE

	/**
	 * Controller action REQUEST CHANGE ROL POST for controller $class->name
	 * autogenerated by CASE IGNITER
	 */
	public function requestChangeRolPost() {
	    
	    
	    // =============================================
	    // ROLE CHECKING START
	    // =============================================
	    
	    if (session_status () == PHP_SESSION_NONE) {session_start ();}
	    \$rol_ok = false;
	    \$login_id = (isset(\$_SESSION['user']) ? \$_SESSION['user']->id: null );
	    if ( \$login_id != null ) { \$rol_ok=true; }
	    if ( !\$rol_ok ) { show_404(); }
	    
	    // =============================================
	    // ROLE CHECKING END
	    // =============================================
	    
	    \$this->load->model('{$class->name}_model');
	    \$roles = ( isset( \$_POST['roles']) ? \$_POST['roles'] : null );
	    
	    try {
	        \$id = \$this->{$class->name}_model->request_roles( \$login_id, \$roles );
	        \$_SESSION['info']['status'] = 'info';
	        \$_SESSION['info']['message'] = 'Roles solicitados. Consulta al administrador del sistema si no son cambiados en breve';
	    }
	    catch (Exception \$e) {
	        \$_SESSION['info']['status'] = 'danger';
	        \$_SESSION['info']['message'] = 'No se pudo solicitar el cambio de rol';
	    }
	    redirect(base_url().'_home/info');
	    
	}
	


CODE;
    return $code;
}

// ------------------------------
function generate_controller_additional_usecase($class, $usecase_name, $roles, $classes)
{
    $role_code = get_role_checking_code(classes_have_login_bean($classes), $roles);
    $code = <<<CODE

	/**
	* Code shell for {$usecase_name} autogenerated by CASE IGNITER
	*/
	public function {$usecase_name}() {
	
		$role_code
		
		frame(\$this,'{$class->name}/$usecase_name');
	}

CODE;
    return $code;
}

// ------------------------------
function generate_controller_change_password($class)
{
    $code = <<<CODE
	
	
	/**
	* Controller action CHANGE PASSWORD for controller {$class->name}
	* autogenerated by CASE IGNITER
	*/
	public function changepwd() {

		if (session_status () == PHP_SESSION_NONE) {session_start ();}

		if (!isset(\$_SESSION['user']) || !isset(\$_SESSION['rol']) || (\$_SESSION['rol']->nombre != 'admin' && \$_SESSION['user']->id != \$_POST['id']) ) {
			show_404();
		}

		\$data['body']['id'] = \$_POST['id'];
		frame(\$this,'{$class->name}/changepwd',\$data);
	}
	
CODE;

    return $code;
}

// ------------------------------
function generate_controller_change_password_post($class)
{
    $code = <<<CODE
	
	
	/**
	* Controller action CHANGE PASSWORD POST for controller {$class->name}
	* autogenerated by CASE IGNITER
	*/
	public function changepwdPost() {
		if (session_status () == PHP_SESSION_NONE) {session_start ();}
		
		if (!isset(\$_SESSION['user']) || !isset(\$_SESSION['rol']) || (\$_SESSION['rol']->nombre != 'admin' && \$_SESSION['user']->id != \$_POST['id']) ) {
			show_404();
		}

		\$id = isset (\$_POST['id']) ? \$_POST['id'] : null ;
		\$old_password = isset (\$_POST['oldPwd']) ? \$_POST['oldPwd'] : null ;
		\$new_password = isset (\$_POST['newPwd']) ? \$_POST['newPwd'] : null ;

		try {
			\$this->load->model('{$class->name}_model');
			\$this->{$class->name}_model->change_password(\$id,\$old_password,\$new_password);
			\$this->load->library('session');
			\$this->session->set_flashdata('id', \$id);
			redirect(base_url().'{$class->name}/update');
		}
		catch (Exception \$e) {
			\$data['status'] = 'error';
			\$data['message'] = "Error al cambiar la contraseña";
			frame(\$this,'{$class->name}/create_message',\$data);
		}
	}
				
CODE;

    return $code;
}

// ------------------------------
function generate_controller_login($class)
{
    $code = <<<CODE
	
	
	/**
	* Controller action LOGIN for controller {$class->name}
	* autogenerated by CASE IGNITER
	*/
	public function login() {
		frame(\$this,'{$class->name}/login');
	}

CODE;

    return $code;
}

// ------------------------------
function generate_controller_change_rol($class)
{
    $code = <<<CODE
	
	
	/**
	* Controller action CHANGE ROL for controller {$class->name}
	* autogenerated by CASE IGNITER
	*/
	public function changeRol() {
		// =============================================
		// ROLE CHECKING START
		// =============================================

		if (session_status () == PHP_SESSION_NONE) {session_start ();}
		\$rol_ok = false;
		if ( \$_SESSION['user'] != null) { \$rol_ok = true; }
		if ( !\$rol_ok ) { show_404(); } 

		// =============================================
		// ROLE CHECKING END
		// =============================================

		\$data['body']['user'] = \$_SESSION['user'];
		frame(\$this,'{$class->name}/changeRol',\$data);
	}
	
CODE;

    return $code;
}

// ------------------------------
function generate_controller_change_rol_post($class)
{
    $code = <<<CODE
	
	
	/**
	* Controller action CHANGE ROL for controller {$class->name}
	* autogenerated by CASE IGNITER
	*/
	public function changeRolPost() {
		// =============================================
		// ROLE CHECKING START
		// =============================================
		
		if (session_status () == PHP_SESSION_NONE) {session_start ();}
		\$rol_ok = false;
		if ( \$_SESSION['user'] != null) { \$rol_ok = true; }
		if ( !\$rol_ok ) { show_404(); }
		
		// =============================================
		// ROLE CHECKING END
		// =============================================
		
		\$new_rol_id = (isset ( \$_POST['rol'] ) ? \$_POST['rol'] : \$_SESSION['rol']->id );
		\$this->load->model('rol_model');
		\$_SESSION['rol'] = \$this->rol_model->get_by_id( \$new_rol_id );
		redirect( base_url() );
	}
				
CODE;

    return $code;
}

// ------------------------------
function generate_controller_request_change_rol($class)
{
    $code = <<<CODE
    
    
	/**
	* Controller action REQUEST CHANGE ROL for controller {$class->name}
	* autogenerated by CASE IGNITER
	*/
	public function requestChangeRol() {
		// =============================================
		// ROLE CHECKING START
		// =============================================
		
		if (session_status () == PHP_SESSION_NONE) {session_start ();}
		\$rol_ok = false;
		if ( \$_SESSION['user'] != null) { \$rol_ok = true; }
		if ( !\$rol_ok ) { show_404(); }
		
		// =============================================
		// ROLE CHECKING END
		// =============================================
		
		\$data['body']['user'] = \$_SESSION['user'];
        \$this->load->model('rol_model');
		\$data['body']['roles'] = \$this->rol_model->get_all();
		frame(\$this,'{$class->name}/requestChangeRol',\$data);
	}
	
CODE;

    return $code;
}

// ------------------------------
function generate_controller_logout($class)
{
    $code = <<<CODE
	
	
	/**
	* Controller action LOGOUT for controller {$class->name}
	* autogenerated by CASE IGNITER
	*/
	public function logout() {
		if (session_status () == PHP_SESSION_NONE) {
			session_start ();
		}
		session_destroy();
		redirect(base_url());
	}
	
CODE;

    return $code;
}

// ------------------------------
function generate_controller_login_post($class)
{
    $redirect_uri = base_url() . '_home';
    $code = <<<CODE
	
	/**
	* Controller action LOGIN POST for controller {$class->name}
	* autogenerated by CASE IGNITER
	*/
	public function loginPost() {
		\$loginname = isset(\$_POST['loginname'])?\$_POST['loginname']:null;
		\$password = isset(\$_POST['password'])?\$_POST['password']:null;

		\$this->load->model('{$class->name}_model');
		\$user = \$this->{$class->name}_model->get_by_loginname(\$loginname);
		
		try {
			if (\$user != null) {
				if ( password_verify(\$password, \$user->password) ) {
						if (sizeof( \$user->ownRolesList ) == 0) { // No rol available
							throw new Exception("Usuario inhabilitado");
						}
						\$rol = \$user->aggr('ownRolesList','rol')[0];
						if (session_status () == PHP_SESSION_NONE) {session_start ();}
	
						\$_SESSION['user'] = \$user;
						\$_SESSION['rol'] = \$rol;

						\$response['severity'] = 'OK';
						\$response['message'] = '$redirect_uri';
						echo json_encode ( \$response );;
				} 
				else { // Bad password
					throw new Exception("Usuario o contraseña incorrecta");
				}
			}
			else { // Bad user
				throw new Exception("Usuario o contraseña incorrecta");
			}
		}
		catch (Exception \$e) {
			\$response['severity'] = 'ERROR';
			\$response['message'] = \$e->getMessage();
			echo json_encode ( \$response );;
		}
	}
	
CODE;
    return $code;
}

// ------------------------------
function generate_controller_list_id($class)
{
    $code = <<<CODE


	/**
	* Controller private function LIST_ID for controller {$class->name}
	* autogenerated by CASE IGNITER
	*/
	private function list_id(\$id) {
		\$this->load->model('{$class->name}_model');
		\$data['body']['{$class->name}'] = [ \$this->{$class->name}_model->get_by_id(\$id) ];
		frame(\$this, '{$class->name}/list', \$data);
	}

CODE;

    return $code;
}

// ------------------------------
function generate_controller_delete($class, $classes)
{
    $rol_check_code = get_role_checking_code(classes_have_login_bean($classes), $class->d);
    $code = <<<CODE


	
	/**
	* Controller action DELETE for controller {$class->name}
	* autogenerated by CASE IGNITER
	*/
	public function delete() {

		$rol_check_code

		\$this -> load -> model ('{$class->name}_model');
		try {
			\$id = \$_POST['id'];
			\$filter = isset (\$_REQUEST['filter'] ) ? \$_REQUEST['filter'] : '';

			\$this -> {$class->name}_model -> delete( \$id );
			redirect(base_url().'{$class->name}/list?filter='.\$filter);
		}
		catch (Exception \$e ) {
			frame(\$this, '{$class->name}/deleteERROR');
		}
	}
CODE;

    return $code;
}

// ------------------------------
function generate_controller_update($class, $classes)
{
    $rol_check_code = get_role_checking_code(classes_have_login_bean($classes), $class->u);
    $code = <<<CODE
	
	
	
	/**
	* Controller action UPDATE for controller {$class->name}
	* autogenerated by CASE IGNITER
	*/
	public function update() {

		$rol_check_code
		
		if (session_status () == PHP_SESSION_NONE) { session_start (); }
		\$is_admin = ( isset(\$_SESSION['rol']) && \$_SESSION['rol']->nombre == 'admin' );
		\$data['body']['is_admin'] = \$is_admin;
		\$data['body']['filter'] = isset(\$_REQUEST['filter']) ? \$_REQUEST['filter'] : '' ;

CODE;

    if ($class->has_dependants()) {
        $types_loaded = [];
        foreach ($class->attributes as $a) {
            if ($a->is_dependant() && (! ($a->hidden_create) || ($a->hidden_create && $class->login_bean && $a->name == 'roles')) && ! in_array($a->type, $types_loaded)) {
                $types_loaded[] = $a->type;
                $code .= generate_controller_update_dependants($a);
            }
        }
    }

    $code .= <<<CODE


		\$this -> load -> model ('{$class->name}_model');
		\$id = (isset (\$_POST['id']) ? \$_POST['id'] : \$_SESSION['id']);
		

		\$data['body']['{$class->name}'] = \$this -> {$class->name}_model -> get_by_id(\$id);
		
		frame(\$this, '{$class->name}/update', \$data);
	}
CODE;

    return $code;
}

// ------------------------------
function generate_controller_update_post($class, $classes)
{
    $code = '';
    $code .= generate_controller_update_post_header($class, $classes);
    $code .= generate_controller_update_post_middle($class);
    $code .= generate_controller_update_post_end($class->name);
    return $code;
}

// ------------------------------
function generate_controller_end()
{
    return <<<CODE

}
?>
CODE;
}

// ------------------------------
function generate_controller_header($class_name)
{
    return <<<CODE
<?php
/**
* Controller code for $class_name autogenerated by CASE IGNITER
*/
class $class_name extends CI_Controller {
CODE;
}

// ------------------------------
function generate_controller_create($class, $classes)
{
    $rol_check_code = get_role_checking_code(classes_have_login_bean($classes), $class->c);
    $code = <<<CODE
	
	
	/**
	* Controller action CREATE for controller {$class->name}
	* autogenerated by CASE IGNITER
	*/
	public function create() {
	
		$rol_check_code		
		\$data['body']['filter'] = isset(\$_REQUEST['filter']) ? \$_REQUEST['filter'] : '' ;
CODE;

    $has_dependants = false;

    if ($class->has_dependants()) {
        $models_loaded = [];
        foreach ($class->attributes as $a) {
            if ($a->is_dependant() && ! ($a->hidden_create) && ! in_array($a->type, $models_loaded)) {
                $models_loaded[] = $a->type;
                $has_dependants = true;
                $code .= <<<CODE

		\$this->load->model('{$a->type}_model');
		\$data['body']['{$a->type}'] = \$this->{$a->type}_model->get_all();

CODE;
            }
        }
    }

    $code .= <<<CODE
	
		frame(\$this, '{$class->name}/create', \$data);
	}
				
				
CODE;
    return $code;
}

// ------------------------------
function generate_controller_create_post($class, $classes)
{
    $code = '';
    $code .= generate_controller_create_post_header($class, $classes);
    $code .= generate_controller_create_post_middle($class);
    $code .= generate_controller_create_post_end($class->name);
    return $code;
}

// ------------------------------
function generate_controller_create_post_middle($class)
{
    $code = '';
    foreach ($class->attributes as $a) {
        if (! $a->hidden_create) {
            if (! $a->collection) {
                if ($a->type == 'file') {
                    $code .= "\t\t\$$a->name = ( isset( \$_FILES['$a->name']) ? \$_FILES['$a->name'] : null );" . PHP_EOL;
                } else if ($class->login_bean && $a->name == 'password') {
                    $code .= "\t\t\$$a->name = ( isset( \$_POST['$a->name']) ? password_hash(\$_POST['$a->name'], PASSWORD_DEFAULT) : password_hash('',PASSWORD_DEFAULT ) );" . PHP_EOL;
                } else {
                    $code .= "\t\t\$$a->name = ( isset( \$_POST['$a->name']) ? \$_POST['$a->name'] : null );" . PHP_EOL;
                }
            } else {
                $code .= "\t\t\$$a->name = ( isset( \$_POST['$a->name']) ? \$_POST['$a->name'] : [] );" . PHP_EOL;
            }
        }
    }

    if ($class->login_bean) {
        $code .= <<<CODE
		
		\$this -> load -> model('rol_model');
		\$default_rol  = \$this->rol_model->get_default_rol();
		if ( \$default_rol == null ) {
			throw new Exception('ERROR: Default rol not defined');
		}
		\$roles = [ \$default_rol->id ];
		
CODE;
    }

    $code .= (PHP_EOL . "\t\ttry {" . PHP_EOL);
    $code .= "\t\t\t\$id = \$this->{$class->name}_model->create( ";

    $parameters = '';
    foreach ($class->attributes as $a) {
        if (! $a->hidden_create || ($class->login_bean && $a->name == 'roles')) {
            $parameters .= "$$a->name, ";
        }
    }

    $parameters = rtrim($parameters, ', ');
    $code .= $parameters;

    $code .= " );" . PHP_EOL;

    $main_attribute = $class->getMainAttribute();
    $capital = ucfirst($class->name);

    // TODO LOC
    $code .= <<<CATCH
			\$this->list_id(\$id);
		}
		catch (Exception \$e) {
		    \$_SESSION['info']['status'] = 'danger';
		    \$_SESSION['info']['message'] = "Error al crear el/la {$class->name} \$$main_attribute";
		    \$_SESSION['info']['link']= '{$class->name}/create';
			redirect(base_url().'_home/info');
		}
CATCH;

    return $code;
}

// ------------------------------
function generate_controller_update_post_middle($class)
{
    $code = '';

    $code .= "\t\t\$id = ( isset( \$_POST['id']) ? \$_POST['id'] : null );" . PHP_EOL;

    foreach ($class->attributes as $a) {
        if (! $a->hidden_create || ($a->hidden_create && $class->login_bean && $a->name == 'roles')) {
            if (! $a->collection) {
                if ($a->type == 'file') {
                    $code .= "\t\t\$$a->name = ( isset( \$_FILES['$a->name']) ? \$_FILES['$a->name'] : null );" . PHP_EOL;
                } else {
                    $code .= "\t\t\$$a->name = ( isset( \$_POST['$a->name']) ? \$_POST['$a->name'] : null );" . PHP_EOL;
                }
            } else {
                $code .= "\t\t\$$a->name = ( isset( \$_POST['$a->name']) ? \$_POST['$a->name'] : [] );" . PHP_EOL;
            }
        }
    }

    if ($class->login_bean) {
        $code .= <<<CODE
		if (session_status () == PHP_SESSION_NONE) {session_start ();}
		\$is_admin = ( isset(\$_SESSION['rol']) && \$_SESSION['rol']->nombre == 'admin' );
CODE;
    }
    $code .= <<<CODE

		try {
			\$this->{$class->name}_model->update( \$id, 
CODE;

    $parameters = '';
    foreach ($class->attributes as $a) {
        if ((! $a->hidden_create && ! ($class->login_bean && $a->name == 'password')) || ($a->hidden_create && $class->login_bean && $a->name == 'roles')) {
            $parameters .= "$$a->name, ";
        }
    }
    if ($class->login_bean) {
        $parameters .= '$is_admin';
    }
    $parameters = rtrim($parameters, ', ');
    $code .= $parameters;

    $code .= " );" . PHP_EOL;

    $main_attribute = $class->getMainAttribute();
    $capital = ucfirst($class->name);

    // TODO LOC
    $code .= <<<CATCH

			\$filter = isset(\$_POST['filter']) ? \$_POST['filter'] : '' ;
			redirect( base_url() . '{$class->name}/list?filter='.\$filter );
		}
		catch (Exception \$e) {
			\$data['status'] = 'error';
			\$data['message'] = "Error al crear el/la {$class->name} \$$main_attribute";
			frame(\$this,'{$class->name}/create_message',\$data);
		}
CATCH;

    return $code;
}

// ------------------------------
function generate_controller_create_post_header($class, $classes)
{
    $rol_check_code = get_role_checking_code(classes_have_login_bean($classes), $class->c);
    $code = <<<CODE
	
	
	/**
	* Controller action CREATE POST for controller $class->name
	* autogenerated by CASE IGNITER
	*/
	public function create_post() {
		
		$rol_check_code
		\$this->load->model('{$class->name}_model');


CODE;
    return $code;
}

// ------------------------------
function generate_controller_update_post_header($class, $classes)
{
    $rol_check_code = get_role_checking_code(classes_have_login_bean($classes), $class->u);

    $code = <<<CODE
	
	
	/**
	* Controller action UPDATE POST for controller $class->name
	* autogenerated by CASE IGNITER
	*/
	public function updatePost() {
	
		$rol_check_code

		\$this->load->model('{$class->name}_model');
			

CODE;
    return $code;
}

// ------------------------------
function generate_controller_update_dependants($attribute)
{
    $code = <<<CODE
	
	
		\$this->load->model('{$attribute->type}_model');
		\$data['body']['{$attribute->type}'] = \$this->{$attribute->type}_model->get_all();
CODE;
    return $code;
}

// ------------------------------
function generate_controller_create_post_end($class_name)
{
    $code = <<<CODE
	
	
	}
				
				
CODE;
    return $code;
}

// ------------------------------
function generate_controller_update_post_end($class_name)
{
    $code = <<<CODE
	
	
	}
			
			
CODE;
    return $code;
}

// ------------------------------
function generate_controller_list($class, $classes)
{
    $rol_check_code = get_role_checking_code(classes_have_login_bean($classes), $class->r);
    $cn = $class->name;
    $code = <<<CODE

	
	/**
	* Controller action LIST for controller {$class->name}
	* autogenerated by CASE IGNITER
	*/
	public function list() {

		$rol_check_code

		\$this->load->model('{$cn}_model');
		\$filter = isset(\$_REQUEST['filter'])?\$_REQUEST['filter']:'';
		\$data['body']['$cn'] = (\$filter == '' ? \$this->{$cn}_model->get_all() : \$this->{$cn}_model->get_filtered( \$filter ) );
		\$data['body']['filter'] = \$filter ;
		frame(\$this, '{$cn}/list', \$data);
	}
CODE;
    return $code;
}

// --------------------------------
function generate_model_get_by_loginname($class)
{
    $code = <<<CODE
	/**
	 * create MODEL action autogenerated by CASE IGNITER
	 */
	public function get_by_loginname( \$loginname ) {
		return R::findOne( '{$class->name}', ' loginname = ? ', [ \$loginname ] );
	}
			
CODE;
    return $code;
}

// --------------------------------
function generate_model_request_roles($class)
{
    $code = <<<CODE


   /**
     * request_roles MODEL action autogenerated by CASE IGNITER
     */
    public function request_roles( \$id, \$roles) {
        \$$class->name = R::load('$class->name',\$id);
        
        // Delete all my previous rolrequests
        \$previousRolRequests = R::find('rolrequest','{$class->name}_id=?', [ \$id ]);
        foreach (\$previousRolRequests as \$rolrequest) {
            R::trash(\$rolrequest);
        }
        
        // Add actual rolrquests
        foreach (\$roles as \$rol_id) {
            \$rol = R::load('rol',\$rol_id);
            \$new_rolrequest = R::dispense('rolrequest');
            \$new_rolrequest->rol = \$rol;
            \$new_rolrequest->$class->name = \$$class->name;
            R::store(\$new_rolrequest);
        }
        
    }


CODE;
    return $code;
}

// --------------------------------
function generate_model_list_change_rol_requests($class)
{
    $code = <<<CODE
    
    /**
    *  LIST CHANGE ROL REQUESTS model action 
    *  autogenerated by CASE IGNITER
    */

    function list_change_rol_requests() {
        return R::findAll('rolrequest');
    }

CODE;

    return $code;
}

// --------------------------------
function generate_model_process_change_rol($class) {
    $code = <<<CODE
    
    /**
    *  PROCESS CHANGE ROL model action
    *  autogenerated by CASE IGNITER
    */
    
    function process_change_rol(\$id, \$mode) {
        \${$class->name} = R::load('{$class->name}', \$id);
        if ( \$mode == 'accept' ) {
            foreach (\${$class->name}->ownRolesList as \$rol) {
                R::trash(\$rol);
            }
            foreach (\${$class->name} -> ownRolrequest as \$rr ) {
                \$rol = R::dispense('roles');
                \$rol -> rol = \$rr->rol;
                \$rol -> {$class->name} = \${$class->name};
                R::store( \$rol );
            }
        }
        foreach (\${$class->name}->ownRolrequestList as \$rol) {
            R::trash(\$rol);
        }
    }
    
CODE;
    
    return $code;
    
}
// --------------------------------
function generate_model($class, $classes)
{
    $code = '';
    $code .= generate_model_header($class->name);
    $code .= generate_model_create($class);
    $code .= generate_model_update($class);
    $code .= generate_model_get_all($class);
    $code .= generate_model_get_filtered($class, $classes);
    $code .= generate_model_delete($class);
    $code .= generate_model_get_by_id($class);
    if ($class->login_bean) {
        $code .= generate_model_get_by_loginname($class);
        $code .= generate_model_change_password($class);
        $code .= generate_model_request_roles($class);
        $code .= generate_model_list_change_rol_requests($class);
        $code .= generate_model_process_change_rol($class);
    }
    if ($class->name == 'rol') {
        $code .= generate_model_get_default_rol();
    }
    $code .= generate_model_end();

    $filename = APPPATH . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . $class->name . '_model.php';
    backup_and_save($filename, $code);
}

// --------------------------------
function generate_model_change_password($class)
{
    return <<<CODE
	/**
	* change_password MODEL action autogenerated by CASEIGNITER
	*/
	public function change_password(\$id,\$old_password,\$new_password) {

		\$bean = R::load('{$class->name}',\$id);
		if ( ! password_verify ( \$old_password, \$bean->password ) ) {
			throw new Exception("ERROR: Contraseña incorrecta");
		}
		else {
			\$bean->password = password_hash ( \$new_password, PASSWORD_DEFAULT );
			R::store(\$bean);
		}
		
	}
CODE;
}

// --------------------------------
function generate_model_get_default_rol()
{
    return <<<CODE
	
	/**
	* get_default_rol_id MODEL action autogenerated by CASEIGNITER
	*/
	public function get_default_rol() {
		return R::findOne('rol', ' nombre = ? ' , [ 'default' ]);
	}

CODE;
}

// --------------------------------
function generate_model_header($class_name)
{
    return <<<CODE
<?php
/**
* Model code autogenerated by CASE IGNITER
*/
class {$class_name}_model extends CI_Model {

CODE;
}

// --------------------------------
function generate_model_delete($class)
{
    $code = <<<CODE

	/**
	* delete MODEL action autogenerated by CASEIGNITER
	*/
	public function delete( \$id ) {
		\$bean = R::load('{$class -> name}', \$id );

CODE;
    foreach ($class->attributes as $a) {
        if ($a->type == 'file') {
            $code .= <<<CODE
		\$file_path = 'assets/upload/'.(\$bean->$a->name);
		if (file_exists( \$file_path )) {
			unlink( \$file_path );
		} 

CODE;
        }
    }
    $code .= "\n\t\tR::trash( \$bean );";
    $code .= "\n\t}";
    return $code;
}

// --------------------------------
function generate_model_get_by_id($class)
{
    $code = <<<CODE
	
	/**
	* get_by_id MODEL action autogenerated by CASEIGNITER
	*/
	public function get_by_id( \$id ) {
		return R::load('{$class -> name}', \$id ) ;
	}
	
CODE;
    return $code;
}

// --------------------------------
function generate_model_create($class)
{
    $code = <<<CODE


	/**
	* create MODEL action autogenerated by CASE IGNITER
	*/
	public function create( 
CODE;

    $parameters = '';

    foreach ($class->attributes as $a) {
        if (! $a->hidden_create || ($class->login_bean && $a->name == 'roles')) {
            $parameters .= "$$a->name, ";
        }
    }
    $parameters = rtrim($parameters, ', ');
    $code .= $parameters;

    $code .= <<<CODE
) {

	R::begin();
	try {

	\$bean = R::dispense( '{$class->name}' );
	\$id_bean = R::store( \$bean );
	
CODE;
    foreach ($class->attributes as $a) {
        if (! $a->hidden_create || ($class->login_bean && $a->name == 'roles')) {
            $type_capitalized = ucfirst($a->type);
            if ($a->mode == "O2O") { // =========== ONE TO ONE RELATIONSHIP ======================
                $code .= <<<O2O
				
	// "one to one" attribute
	if ( \${$a->name} != null && \${$a->name} != 0 ) {
		\$o2o = R::load('{$a->type}',\${$a->name});
		\$bean -> {$a->name} = \$o2o;
		R::store(\$bean);
		\$o2o -> {$a->name} = \$bean;
		R::store(\$o2o);
	}
				
				
O2O;
            } else if ($a->mode == "M2O") { // =========== MANY TO ONE RELATIONSHIP ======================
                $code .= <<<M2O

	// "many to one" attribute
	if ( \${$a->name} != null && \${$a->name} != 0) {
		\$bean -> {$a->name} = R::load('{$a->type}',\${$a->name});
	}
				
				
M2O;
            } else if ($a->mode == "O2M") { // =========== ONE TO MANY RELATIONSHIP ======================
                $code .= <<<O2M
					
	// "one to many" attribute
	foreach (\${$a->name} as \$id) {
		\$o2m = R::load('{$a->type}', \$id);
		\$bean -> alias('{$a->name}') ->own{$type_capitalized}List[] = \$o2m;
		R::store(\$bean);
	}
				
				
O2M;
            } else if ($a->mode == "M2M") { // =========== MANY TO MANY RELATIONSHIP ======================
                $code .= <<<M2M
					
	// "many to many" attribute
	foreach (\${$a->name} as \$id) {
		\$another_bean = R::load('{$a->type}', \$id);
		\$m2m = R::dispense('{$a->name}');
		R::store(\$bean);
		\$m2m -> {$class->name} = \$bean;
		\$m2m -> {$a->type} = \$another_bean;
		R::store(\$m2m);
	}
				
				
M2M;
            } else {
                if ($a->type == 'file') { // ============ REGULAR FILE ATTRIBUTE ======================
                    $code .= <<<REGULARFILE
					
	// Regular FILE attribute
	if ( \${$a->name} != NULL && \${$a->name}['error'] == UPLOAD_ERR_OK) {
		\$name_and_ext = explode ( '.', \${$a->name}['name'] );
		\$ext = \$name_and_ext[sizeof ( \$name_and_ext ) - 1 ];
		\$file_name = '{$class->name}' . '-' . '{$a->name}' . '-' . \$id_bean . '.' .\$ext ;
		copy ( \${$a->name}['tmp_name'], 'assets/upload/' .  \$file_name );
		\$bean -> {$a->name} = \$file_name;	
	}

REGULARFILE;
                } else { // ================================ REGULAR ATTRIBUTE ===========================
                    $code .= <<<REGULAR

	// Regular attribute
	\$bean -> {$a->name} = \${$a->name};

REGULAR;
                }
            }
        }
    }

    $code .= <<<CODE

	R::store(\$bean);
	R::commit();
	return \$bean->id;

	}
	catch (Exception \$e) {
		R::rollback();
		throw \$e;
	}

	}

CODE;
    return $code;
}

// --------------------------------
function generate_model_update($class)
{
    $code = <<<CODE

	/**
	* update MODEL action autogenerated by CASE IGNITER
	*/
	public function update( \$id, 
CODE;

    $parameters = '';
    foreach ($class->attributes as $a) {
        if ((! $a->hidden_create && ! ($class->login_bean && $a->name == 'password')) || ($a->hidden_create && $class->login_bean && $a->name == 'roles')) {

            // if (! $a->hidden_create || ($a->hidden_create && $class->login_bean && $a->name == 'roles')) {
            $parameters .= "$$a->name, ";
        }
    }
    if ($class->login_bean) {
        $parameters .= '$is_admin';
    }
    $parameters = rtrim($parameters, ', ');
    $code .= $parameters;

    $code .= <<<CODE
) {

	R::begin();

	try {
	\$bean = R::load( '{$class->name}', \$id );

CODE;
    foreach ($class->attributes as $a) {
        if ((! $a->hidden_create && ! ($class->login_bean && $a->name == 'password')) || ($a->hidden_create && $class->login_bean && $a->name == 'roles')) {

            // if (! $a->hidden_create || ($a->hidden_create && $class->login_bean && $a->name == 'roles')) {
            $type_capitalized = ucfirst($a->type);
            $name_capitalized = ucfirst($a->name);

            if ($a->mode == "O2O") { // =========== ONE TO ONE RELATIONSHIP ======================
                $code .= <<<O2O

	// "one to one" attribute
	if ( \${$a->name} != null ) {
		\$o2o = ( \${$a->name} != 0 ? R::load('{$a->type}',\${$a->name}) : null );

		if (\$bean->fetchAs('{$a->type}')->{$a->name} != null ) {
			\$o2o_prev = R::load('{$a->type}',\$bean->fetchAs('{$a->type}')->{$a->name}->id);
			\$o2o_prev -> {$a->name}_id = null;
			R::store(\$o2o_prev);
		}

		\$bean -> {$a->name} = \$o2o;

		R::store(\$bean);

		if ( \$o2o != null ) {
			\$o2o -> {$a->name} = \$bean;
			R::store(\$o2o);
		}
	}


O2O;
            } else if ($a->mode == "M2O") { // =========== MANY TO ONE RELATIONSHIP ======================
                $code .= <<<M2O
	// "many to one" attribute
	if ( \${$a->name} != null ) {
		\$bean -> {$a->name} = ( \${$a->name} != 0 ? R::load('{$a->type}',\${$a->name}) : null );
		R::store(\$bean);
	}


M2O;
            } else if ($a->mode == "O2M") { // =========== ONE TO MANY RELATIONSHIP ======================
                $code .= <<<O2M
				
	// "one to many" attribute (O2M)

	foreach (\$bean->alias('{$a->name}')->own{$type_capitalized}List as \${$a->name}_bean ) {
		\$key = array_search( \${$a->name}_bean->{$a->name}->id, \${$a->name} );
		
		if (\$key !== false) { // O2M we keep only the keys to add
			unset(\${$a->name}[\$key]);
		}
		else { // O2M Element to be deleted
			R::store(\$bean);
			\${$a->name}_bean -> {$a->name} = null;
			R::store(\${$a->name}_bean);
		}
	}

	// O2M Elements to be added
	foreach (\${$a->name} as \$idf) {
		\$o2m = R::load('{$a->type}', \$idf);
		\$o2m -> {$a->name} = \$bean;
		R::store(\$o2m);
	}


O2M;
            } else if ($a->mode == "M2M") { // =========== MANY TO MANY RELATIONSHIP ======================
                $if_for_roles_begin = '';
                $if_for_roles_end = '';
                if ($class->login_bean && $a->name == 'roles') {
                    $if_for_roles_begin = "if ( \$is_admin ) {\n";
                    $if_for_roles_end = '}';
                }
                $code .= <<<M2M
				
	// "many to many" attribute (M2M)
	
	$if_for_roles_begin
	foreach (\$bean->own{$name_capitalized}List as \${$a->name}_bean ) {
		\$key = array_search( \${$a->name}_bean->{$a->type}->id, \${$a->name} );
		
		if (\$key !== false) { // M2M we keep only the keys to add
			unset(\${$a->name}[\$key]);
		}
		else { // M2M Element to be deleted
			R::store(\$bean);
			R::trash(\${$a->name}_bean);
		}
	}

	// M2M Elements to be added
	foreach (\${$a->name} as \$idf) {
		\$another_bean = R::load('{$a->type}', \$idf);
		\$m2m = R::dispense('{$a->name}');
		\$m2m -> {$class->name} = \$bean;
		\$m2m -> {$a->type} = \$another_bean;
		R::store(\$m2m);
	}
	$if_for_roles_end

	
M2M;
            } else {
                if ($a->type == 'file') { // ============ REGULAR FILE ATTRIBUTE ======================
                    $code .= <<<REGULARFILE
					
	// Regular FILE attribute
	if ( \${$a->name} != NULL && \${$a->name}['error'] == UPLOAD_ERR_OK) {
		\$name_and_ext = explode ( '.', \${$a->name}['name'] );
		\$ext = \$name_and_ext[sizeof ( \$name_and_ext ) - 1 ];
		\$file_name = '{$class->name}' . '-' . '{$a->name}' . '-' . \$id . '.' .\$ext ;
		copy ( \${$a->name}['tmp_name'], 'assets/upload/' .  \$file_name );
		if (\$bean->{$a->name} != null && \$bean->{$a->name} != '' ) {
			unlink( 'assets/upload/'.\$bean->{$a->name} );
		}
		\$bean -> {$a->name} = \$file_name;
	}
	
REGULARFILE;
                } else { // ================================ REGULAR ATTRIBUTE ===========================
                    $code .= <<<REGULAR
					
	// Regular attribute
	\$bean -> {$a->name} = \${$a->name};
	
REGULAR;
                }
            }
        }
    }

    $code .= <<<CODE

	R::store(\$bean);
	R::commit();
	}
	catch (Exception \$e) {
		R::rollback();
		throw \$e;
	}

	}

CODE;
    return $code;
}

// --------------------------------
function generate_model_get_all($class)
{
    $code = <<<CODE

	/**
	* get_all MODEL action autogenerated by CASE IGNITER
	*/
	public function get_all() {
		return R::findAll('{$class->name}');
	}

CODE;
    return $code;
}

// --------------------------------
function generate_model_get_filtered($class, $classes)
{
    $code = <<<CODE

	/**
	* get_filtered MODEL action autogenerated by CASE IGNITER
	*/
	public function get_filtered(\$filter) {

		\$where_clause = [ ];

CODE;
    $param_count = 0;
    foreach ($class->attributes as $a) {
        if (! $a->hidden_recover) {
            $param_count ++;
            if (! $a->is_dependant()) { // REGULAR ATTRIBUTE
                $code .= "\n\t\t\$where_clause[] = '{$a->name} LIKE ?';";
            } else {
                if ($a->mode == 'O2O' || $a->mode == 'M2O') { // O2O or M2O
                    $foreign_name = getMainAttributeName($classes, $a->type);
                    $code .= "\n\t\t\$where_clause[] = " . "'(SELECT $foreign_name FROM {$a->type} WHERE {$a->type}.id = {$class->name}.{$a->name}_id) LIKE ?';";
                } else if ($a->mode == 'O2M') { // O2M
                    $foreign_name = getMainAttributeName($classes, $a->type);
                    $code .= "\n\t\t\$where_clause[] = " . "'(SELECT count(*) FROM {$a->type} WHERE $foreign_name LIKE ? AND {$a->name}_id = {$class->name}.id) > 0';";
                } else if ($a->mode == 'M2M') {
                    $foreign_name = getMainAttributeName($classes, $a->type);
                    $code .= "\n\t\t\$where_clause[] = " . "'(SELECT count(*) FROM {$a->type} WHERE $foreign_name LIKE ? AND {$a->type}.id IN (SELECT {$a->type}_id FROM {$a->name} WHERE {$class->name}_id = {$class->name}.id)) > 0';";
                }
            }
        }
    }

    $params = [];
    for ($i = 0; $i < $param_count; $i ++) {
        $params[] = '$f';
    }
    $params = implode(',', $params);

    $code .= <<<CODE

		\$f = "%\$filter%";
		
		return R::findAll('{$class->name}', implode(' OR ', \$where_clause) , [ $params ] );
		
	}

CODE;
    return $code;
}

// --------------------------------
function generate_model_end()
{
    $code = <<<CODE

}
?>
CODE;
    return $code;
}

// ------------------------------
function generate_view_request_change_rol($class, $classes)
{
    $code = <<<CODE
    
    
<div class="container">

<h2>Solicitud de cambio de rol</h2>

<h4>Escoge el nuevo rol (o roles) que quieres desempeñar</h4>

<form class="form" role="form" id="idForm" action="<?= base_url() ?>{$class->name}/requestChangeRolPost" method="post">


		<?php foreach (\$body['roles'] as \$rol ): ?>
			<?php if (\$rol->nombre != 'admin'): ?>
            	<div class="row form-group form-check form-check-inline offset-1">
        			<input  class="form-check-input" type="checkbox" id="id-roles-<?=\$rol->id ?>" name="roles[]" value="<?= \$rol->id ?>" <?php if (\$rol->nombre == 'default'): ?>checked="checked"<?php endif; ?> >
        			<label class="form-check-label" for="id-roles-<?=\$rol->id?>" ><?= \$rol->descripcion ?></label>
               	</div>
    		<?php endif; ?>
		<?php endforeach; ?>
	
	<div class="row offset-2 col-6">
		<input type="submit" class="btn btn-primary" value="Entrar">
		<a href="<?=base_url()?>">
			<input type="button" class="offset-1 btn btn-primary" value="Cancelar">
		</a>
	</div>
	
</form>

</div>

CODE;
    file_put_contents(APPPATH . 'views' . DIRECTORY_SEPARATOR . $class->name . DIRECTORY_SEPARATOR . 'requestChangeRol.php', $code);
}

// ------------------------------
function generate_view_list_change_rol_requests($class, $classes)
{
    $code = <<<CODE

<div class="container">
<h1>LISTA de cambios de ROL pendientes</h1>

<table id="myTable" class="table table-hover table-striped tablesorter">
	<thead>
	<tr>
		<th>Nombre</th>		
		<th>Loginname</th>
		<th>Roles solicitados</th>
		<th>Aceptar / rechazar</th>
	</tr>
	</thead>

	<tbody>
	<?php \$visited=[];?>
	<?php foreach (\$body['rolrequests'] as \$rr): ?>
		<?php if (!in_array(\$rr->{$class->name}_id,\$visited )): ?>
    		<?php \$visited[] = \$rr->{$class->name}_id; \${$class->name} = \$rr->{$class->name}; ?>
    		
		<tr>
			<td class="alert alert-success"><?=  \${$class->name} -> nombre ?></td>

			<td>
				<?= \${$class->name} -> loginname ?></td>
			<td>
			<?php foreach (\${$class->name} -> aggr('ownRolrequestList', 'rol') as \$rol): ?>
				<span><?= \$rol -> nombre ?></span>
			<?php endforeach; ?>
			</td>
				
			<td class="form-inline text-center">

				<form id="id-accept-<?= \${$class->name}->id ?>" action="<?= base_url() ?>{$class->name}/acceptRC" method="post" class="form-group">
					<input type="hidden" name="id" value="<?= \${$class->name} -> id ?>">
					<button onclick="getElementById('id-accept').submit()">
						<img src="<?= base_url() ?>assets/img/icons/png/check-2x.png" height="15" width="15" alt="aceptar">
					</button>
				</form>

				<form id="id-reject-<?= \${$class->name}->id ?>" action="<?= base_url() ?>{$class->name}/rejectRC" method="post" class="form-group">
					<input type="hidden" name="id" value="<?= \${$class->name} -> id ?>">
					<button onclick="getElementById('id-reject').submit()">
						<img src="<?= base_url() ?>assets/img/icons/png/x-2x.png" height="15" width="15" alt="rechazar">
					</button>
				</form>

			</td>

		</tr>
		<?php endif;?>
	<?php endforeach; ?>
	</tbody>
</table>
</div>



CODE;
    file_put_contents(APPPATH . 'views' . DIRECTORY_SEPARATOR . $class->name . DIRECTORY_SEPARATOR . 'listChangeRolRequests.php', $code);
}

// ------------------------------
function generate_view($class, &$classes)
{
    $dirname = APPPATH . 'views' . DIRECTORY_SEPARATOR . $class->name;

    if (! file_exists($dirname)) {
        mkdir($dirname);
    }

    generate_view_create($class, $classes);
    generate_view_create_post($class);
    generate_view_create_message($class);
    generate_view_list($class, $classes);
    generate_view_update($class, $classes);
    if ($class->login_bean) {
        generate_view_login($class, $classes);
        generate_view_change_password($class, $classes);
        generate_view_change_rol($class, $classes);
        generate_view_request_change_rol($class, $classes);
        generate_view_list_change_rol_requests($class, $classes);
    }
    if ($class->usecases != []) {
        foreach ($class->usecases as $usecase) {
            foreach ($usecase as $usecase_name => $roles) {
                generate_view_additional_usecase($class, $usecase_name, $roles);
            }
        }
    }
}

// ------------------------------
function generate_view_additional_usecase($class, $usecase_name, $roles)
{
    $code = <<<CODE
	
	
<div class="container">

<h2> Vista para $usecase_name </h2>
		
</div>

CODE;

    file_put_contents(APPPATH . 'views' . DIRECTORY_SEPARATOR . $class->name . DIRECTORY_SEPARATOR . $usecase_name . '.php', $code);
}

// ------------------------------
function generate_view_change_rol($class, $classes)
{
    $code = <<<CODE
		
		
<div class="container">

<h2> Cambio de rol </h2>

<h4>Elige el nuevo rol que quieres desempeñar</h4>

<form class="form" role="form" id="idForm" action="<?= base_url() ?>{$class->name}/changeRolPost" method="post">
		

	<div class="row form-inline form-group">
		<label for="id-rol" class="col-2 justify-content-end">Nuevo rol</label>
		<select id="id-rol" name="rol" class="col-6 form-control">
			<?php foreach (\$body['user']->aggr('ownRolesList','rol') as \$rol ): ?>
				<option value="<?= \$rol->id ?>"><?= \$rol->descripcion ?></option>
			<?php endforeach; ?>
		</select>
	</div>

	<div class="row offset-1">
		Si el rol buscado no está en la lista, solicita al administrador un cambio de rol pulsando&nbsp;
		<a href="<?= base_url() ?>{$class->name}/requestChangeRol">aquí</a>
	</div>
	
	<div class="row offset-2 col-6">
		<input type="submit" class="btn btn-primary" value="Entrar">
		<a href="<?=base_url()?>">
			<input type="button" class="offset-1 btn btn-primary" value="Cancelar">
		</a>
	</div>
		
</form>
		
</div>

CODE;
    file_put_contents(APPPATH . 'views' . DIRECTORY_SEPARATOR . $class->name . DIRECTORY_SEPARATOR . 'changeRol.php', $code);
}

// ------------------------------
function generate_view_change_password($class, $classes)
{
    $code = <<<CODE
	
	
<div class="container">

<h2> Cambio de contraseña </h2>

<form class="form" role="form" id="idForm" action="<?= base_url() ?>{$class->name}/changepwdPost" method="post">
		
	<input type="hidden" name="id" value="<?=\$body['id']?>"/>

	<div class="row form-inline form-group">
		<label for="id-loginname" class="col-2 justify-content-end">Antigua contraseña</label>
		<input id="id-loginname" type="password" name="oldPwd" class="col-6 form-control" autofocus="autofocus">
	</div>
		
	<div class="row form-inline form-group">
		<label for="id-password" class="col-2 justify-content-end">Nueva contraseña</label>
		<input id="id-password" type="password" name="newPwd" class="col-6 form-control" >
	</div>
		
	<div class="row offset-2 col-6">
		<input type="submit" class="btn btn-primary" value="Entrar">
		<a href="<?=base_url()?>">
			<input type="button" class="offset-1 btn btn-primary" value="Cancelar">
		</a>
	</div>
		
</form>
		
</div>

CODE;
    file_put_contents(APPPATH . 'views' . DIRECTORY_SEPARATOR . $class->name . DIRECTORY_SEPARATOR . 'changepwd.php', $code);
}

// ------------------------------
function generate_view_login($class, $classes)
{
    $jquery_ajax_code = get_jquery_ajax_code($class->name . '/loginPost');
    $modal_code = get_modal_code();
    $code = <<<CODE

<div class="container">

$modal_code
	
$jquery_ajax_code

<h2> Bienvenido </h2>
<h5> Introduce tus credenciales</h5>

<form class="form" role="form" id="id-form">

	<div class="row form-inline form-group">
		<label for="id-loginname" class="col-2 justify-content-end">Usuario</label>
		<input id="id-loginname" type="text" name="loginname" class="col-6 form-control" autofocus="autofocus">
	</div>

	<div class="row form-inline form-group">
		<label for="id-password" class="col-2 justify-content-end">Contraseña</label>
		<input id="id-password" type="password" name="password" class="col-6 form-control" >
	</div>

	<div class="row offset-2 col-6">
		<input type="submit" class="btn btn-primary" value="Entrar">
		<a href="<?=base_url()?>">
			<input type="button" class="offset-1 btn btn-primary" value="Cancelar">
		</a>
	</div>
	
</form>

</div>

CODE;
    file_put_contents(APPPATH . 'views' . DIRECTORY_SEPARATOR . $class->name . DIRECTORY_SEPARATOR . 'login.php', $code);
}

// ------------------------------
function generate_view_create($class, $classes)
{
    $code = '';
    // $code .= generate_view_create_ajax ( $class->name );
    $code .= generate_view_create_header($class->name);
    $code .= generate_view_create_non_dependants($class);
    $code .= generate_view_create_dependants($class, $classes);
    $code .= generate_view_create_end($class->name);

    file_put_contents(APPPATH . 'views' . DIRECTORY_SEPARATOR . $class->name . DIRECTORY_SEPARATOR . 'create.php', $code);
}

// ------------------------------
function generate_view_update($class, &$classes)
{
    $code = '';
    $code .= generate_view_update_header($class);
    $code .= generate_view_update_non_dependants($class, $classes);
    $code .= generate_view_update_dependants($class, $classes);
    $code .= generate_view_update_end($class->name);

    file_put_contents(APPPATH . 'views' . DIRECTORY_SEPARATOR . $class->name . DIRECTORY_SEPARATOR . 'update.php', $code);
}

// ------------------------------
function generate_view_create_message($class)
{
    $code = <<<CODE
<div class="container">
	<?php if (\$status == 'ok' ): ?>
	<div class="alert alert-success"><?= \$message ?></div>
	<?php else: ?>
	<div class="alert alert-danger"><?= \$message ?></div>
	<?php endif; ?>
</div>
CODE;

    file_put_contents(APPPATH . 'views' . DIRECTORY_SEPARATOR . $class->name . DIRECTORY_SEPARATOR . 'create_message.php', $code);
}

// ------------------------------
function get_role_checking_code($has_login_bean, $roles = [])
{
    $code = '';
    if ($has_login_bean) {
        $roles[] = 'admin';
        $code .= <<<CODE

		// =============================================
		// ROLE CHECKING START
		// =============================================

		if (session_status () == PHP_SESSION_NONE) {session_start ();}
		\$rol_ok = false;
		\$login_rol = (isset(\$_SESSION['rol']) ? \$_SESSION['rol']->nombre : null );
		\$login_id = (isset(\$_SESSION['user']) ? \$_SESSION['user']->id: null );
		\$bean_id = (isset(\$_POST['id']) ? \$_POST['id']: null );
CODE;
        $conditions = [];
        foreach ($roles as $rol) {
            if ($rol == 'all') {}
            if ($rol == 'auth') {
                $conditions[] = "\$login_rol != null";
            } else if ($rol == 'anon') {
                $conditions[] = "\$login_rol == null";
            } else if ($rol == 'me') {
                $conditions[] = "\$login_id == \$bean_id";
            } else { // Explicit rol
                $conditions[] = "\$login_rol == '$rol'";
            }
        }
        $conditions_clause = implode(' || ', $conditions);
        $if_conditions = $conditions != [] ? "if ( $conditions_clause ) { \$rol_ok=true; }" : '';
        $code .= <<<CODE

		$if_conditions
		if ( !\$rol_ok ) { show_404(); } 

		// =============================================
		// ROLE CHECKING END
		// =============================================

CODE;
    }
    return $code;
}

// ------------------------------
function generate_view_create_ajax($class_name)
{
    $code = <<<JS

<script type="text/javascript" src="<?= base_url() ?>assets/js/serialize.js"></script>

<script type="text/javascript">
var connection;

function detect(e) {
		key = document.all ? e.keyCode : e.which;
		if (key==13) {
			create();
		}
	}

function create() {
	var createForm = document.getElementById('idForm');
	var serializedData = serialize(createForm);
	
	connection = new XMLHttpRequest();
	connection.open('POST', '<?= base_url() ?>$class_name/create_post', true);
	connection.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
	connection.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
	connection.send(serializedData);
	connection.onreadystatechange = function() {
		if (connection.readyState==4 && connection.status==200) {
			actionAJAX();
		}
	}
}

		
function actionAJAX() {
	htmlReceived = connection.responseText;
	document.getElementById("idMessage").innerHTML = htmlReceived;
}	

</script>

<!-- ----------------------------------------- -->


JS;
    return $code;
}

// ------------------------------
function generate_view_create_header($class_name)
{
    $jquery_ajax_code = get_jquery_ajax_code($class_name . '/create_post');
    $modal_code = get_modal_code();
    $code = <<<HTML


<div class="container">

$jquery_ajax_code
	
$modal_code
	
<h2> Crear $class_name </h2>

<form class="form" role="form" id="idForm" enctype="multipart/form-data" action="<?= base_url() ?>$class_name/create_post" method="post">


HTML;

    return $code;
}

// ------------------------------
function generate_view_update_header($class)
{
    $code = "<?php\n";

    $code .= code_get_ids();

    $code .= <<<HTML

	function selected(\$bean_selected, \$id_to_be_tested) {
		return \$bean_selected != null && \$bean_selected->id == \$id_to_be_tested ? 'selected="selected"' : '';
	}

	function checked(\$list, \$id_to_be_tested) {
		return in_array(\$id_to_be_tested, get_ids(\$list) ) ? 'checked="checked"' : '';
	}
?>	
	
<div class="container">
<h2> Editar {$class->name} </h2>

HTML;
    if ($class->login_bean) {
        $code .= <<<CODE
		
<form action="<?=base_url()?>{$class->name}/changepwd" method="post">
	<input type="hidden" name="id" value="<?= \$body['{$class->name}']->id ?>">
	<input type="submit" class="offset-1 btn btn-primary" value="Cambiar contraseña">
</form>
				
CODE;
    }

    $code .= <<<HTML

<form class="form" role="form" id="idForm" enctype="multipart/form-data" action="<?= base_url() ?>{$class->name}/updatePost" method="post">
	
	<input type="hidden" name="filter" value="<?=\$body['filter']?>" />
	
		
HTML;

    return $code;
}

// ------------------------------
function generate_view_create_non_dependants($class)
{
    $code = '';
    foreach ($class->attributes as $a) {
        if (! $a->is_dependant() && ! $a->hidden_create) {
            $capitalized = ucfirst($a->name);
            $type = ($a->type == 'String' ? 'text' : $a->type);
            $type = (($class->login_bean && $a->name == 'password') ? 'password' : $type);
            $autofocus = $a->main ? 'autofocus="autofocus"' : '' ;
            $required = $a->notnull ? 'required="required"' : '' ;
            $size = $a->type == 'date' ? '3' : '6';
            $preview = ($a->type == 'file' ? "<img class=\"offset-1 col-2\" id=\"id-out-{$a->name}\" width=\"3%\" height=\"3%\" src=\"\" alt=\"\"/>" : '');
            $jquery_file_code = $a->type != 'file' ? '' : <<<CODE

	<script>
		 $(window).on("load",(function(){
		 $(function() {
		 $('#id-{$a->name}').change(function(e) {addImage(e);});
		function addImage(e){
			var file = e.target.files[0],
			imageType = /image.*/;
			if (!file.type.match(imageType)) return;
			var reader = new FileReader();
			reader.onload = fileOnload;
			reader.readAsDataURL(file);
		}
		function fileOnload(e) {
		var result=e.target.result;
		$('#id-out-{$a->name}').attr("src",result);
		}});}));
	</script>


CODE;
            $code .= <<<HTML
	
	$jquery_file_code
	
	<div class="row form-inline form-group">
		<label for="id-{$a->name}" class="col-2 justify-content-end">$capitalized</label>
		<input id="id-{$a->name}" type="$type" name="{$a->name}" class="col-$size form-control" $autofocus $required>
		$preview
	</div>


HTML;
        }
    }
    return $code;
}

// ------------------------------
function generate_view_update_non_dependants($class, $classes)
{
    $code = <<<CODE

	<input type="hidden" name="id" value="<?= \$body['{$class->name}']->id ?>">

CODE;

    foreach ($class->attributes as $a) {
        if (! $a->is_dependant() && ! ($class->login_bean && $a->name == 'password') && ! $a->hidden_create) {
            $capitalized = ucfirst($a->name);
            $type = ($a->type == 'String' ? 'text' : $a->type);
            $size = $a->type == 'date' ? '3' : '6';
            $jquery_file_code = <<<CODE
			
	<script>
		 $(window).on("load",(function(){
		 $(function() {
		 $('#id-{$a->name}').change(function(e) {addImage(e);});
		function addImage(e){
			var file = e.target.files[0],
			imageType = /image.*/;
			if (!file.type.match(imageType)) return;
			var reader = new FileReader();
			reader.onload = fileOnload;
			reader.readAsDataURL(file);
		}
		function fileOnload(e) {
		var result=e.target.result;
		$('#id-out-{$a->name}').attr("src",result);
		}});}));
	</script>
				
				
CODE;
            $code .= ($a->type == 'file' ? $jquery_file_code : '');
            $src = "<?=base_url().'assets/upload/'.\$body['{$class->name}']->{$a->name}?>";
            $required = $a->notnull ? 'required="required"' : '' ;
            $preview = ($a->type == 'file' ? "<img class=\"offset-1 col-2\" id=\"id-out-{$a->name}\" width=\"3%\" height=\"3%\" src=\"$src\" alt=\"\"/>" : '');

            $code .= <<<HTML

	<div class="row form-inline form-group" >
		<label for="id-{$a->name}" class="col-2 justify-content-end">$capitalized</label>
		<input id="id-{$a->name}" type="$type" name="{$a->name}" value="<?=  \$body['{$class->name}']->{$a->name} ?>" class="col-$size form-control" $required>
		$preview
	</div>
				
				
HTML;
        }
    }
    return $code;
}

// ------------------------------
function generate_view_create_dependants($class, $classes)
{
    $code = '';

    foreach ($class->attributes as $a) {

        if ($a->is_dependant() && ! $a->hidden_create) {
            $name_capitalized = ucfirst(explode('_', $a->name)[0]);
            $type_plural_capitalized = ucfirst(plural($a->type));
            $main_attribute = getMainAttributeName($classes, $a->type);
            $legend_2m = ($a->mode == 'M2M' ? $type_plural_capitalized : ($a->mode == 'O2M') ? $name_capitalized : 'dontCare');

            if ($a->mode == 'M2O' || $a->mode == 'O2O') {
                $if_O2O_begin = ($a->mode != 'O2O' ? '' : "<?php if ( \${$a->type}->{$a->name}_id == null ): ?>");
                $if_O2O_end = ($a->mode != 'O2O' ? '' : '<?php endif; ?> ');
                $code .= <<<SELECT_CODE

	<div class="row form-inline form-group">
		<label for="id-{$a->name}" class="col-2 justify-content-end">$name_capitalized</label>
		<select id="id-{$a->name}" name="{$a->name}" class="col-6 form-control">
			<option value="0"> ----- </option>
			<?php foreach (\$body['{$a->type}'] as \${$a->type} ): ?>
				$if_O2O_begin
					<option value="<?= \${$a->type}->id ?>"><?= \${$a->type}->$main_attribute ?></option>
				$if_O2O_end
			<?php endforeach; ?>
		</select>
	</div>


SELECT_CODE;
            }
            if ($a->mode == 'O2M' || $a->mode == 'M2M') {
                $if_O2M_begin = ($a->mode != 'O2M' ? '' : "<?php if ( \${$a->type}->{$a->name}_id == null ): ?>");
                $if_O2M_end = ($a->mode != 'O2M' ? '' : '<?php endif; ?> ');
                $code .= <<<CHECKBOX_CODE

	<div class="row form-inline form-group">

		<label class="col-2 justify-content-end">$legend_2m</label>
		<div class="col-6 form-check form-check-inline justify-content-start">

			<?php foreach (\$body['{$a->type}'] as \${$a->type} ): ?>
				$if_O2M_begin
					<div class="form-check form-check-inline">
						<input class="form-check-input" type="checkbox" id="id-{$a->name}-<?=\${$a->type}->id?>" name="{$a->name}[]" value="<?= \${$a->type}->id ?>">
						<label class="form-check-label" for="id-{$a->name}-<?=\${$a->type}->id?>" ><?= \${$a->type}->$main_attribute ?></label>
					</div>
				$if_O2M_end
			<?php endforeach; ?>
		</div>
	</div>


CHECKBOX_CODE;
            }
        }
    }

    return $code;
}

// ------------------------------
function code_get_ids()
{
    $code = <<<CODE


	function get_ids(\$beans) {
		\$sol = [];
		foreach (\$beans as \$bean) {
			\$sol[] = \$bean -> id;
		}
		return \$sol;
	}

CODE;

    return $code;
}

// ------------------------------
function generate_view_update_dependants($class, $classes)
{
    $code = '';

    foreach ($class->attributes as $a) {

        if ($a->is_dependant() && (! ($a->hidden_create) || ($a->hidden_create && $class->login_bean && $a->name == 'roles'))) {
            $name_capitalized = ucfirst(explode('_', $a->name)[0]);
            $type_capitalized = ucfirst($a->type);
            $type_plural_capitalized = ucfirst(plural($a->type));
            $main_attribute = getMainAttributeName($classes, $a->type);

            $legend_2m = ($a->mode == 'M2M' ? $type_plural_capitalized : ($a->mode == 'O2M') ? $name_capitalized : 'dontCare');

            if ($a->mode == 'M2O' || $a->mode == 'O2O') {
                $if_O2O_start = $a->mode == 'O2O' ? "<?php if ( \${$a->type} -> {$a->name}_id == null  || \${$a->type} -> fetchAs('{$class->name}') -> {$a->name} -> id == \$body['{$class->name}']->id ): ?>" : '';
                $if_O2O_end = $a->mode == 'O2O' ? '<?php endif; ?>' : '';
                $code .= <<<SELECT_CODE
				
	<div class="row form-inline form-group">
		<label for="id-{$a->name}" class="col-2 justify-content-end">$name_capitalized</label>
		<select id="id-{$a->name}" name="{$a->name}" class="col-6 form-control">
			<option value="0" <?= \$body['{$class->name}']->fetchAs('{$a->type}')->{$a->name} == null ? 'selected="selected"' : '' ?> > ----- </option> 
		<?php foreach (\$body['{$a->type}'] as \${$a->type} ): ?>
			$if_O2O_start
			<option value="<?= \${$a->type}->id ?>" <?= selected(\$body['{$class->name}']->fetchAs('{$a->type}')->{$a->name}, \${$a->type}->id ) ?>><?= \${$a->type}->$main_attribute ?></option>
			$if_O2O_end
		<?php endforeach; ?>
					
		</select>
	</div>
					
SELECT_CODE;
            }
            if ($a->mode == 'O2M' || $a->mode == 'M2M') {

                $checked_string = ($a->mode == 'M2M' ? "<?= checked(\$body['{$class->name}']->aggr('own{$name_capitalized}List','$a->type'), \${$a->type}->id ) ?>" : "<?= checked(\$body['{$class->name}']->alias('{$a->name}')->own{$type_capitalized}List, \${$a->type}->id ) ?>");
                $if_O2M_start = $a->mode == 'O2M' ? "<?php if ( \${$a->type} -> fetchAs('{$class->name}') -> {$a->name} == null || \${$a->type} -> fetchAs('{$class->name}') -> {$a->name} -> id == \$body['{$class->name}']->id ): ?>" : '';
                $if_O2M_end = $a->mode == 'O2M' ? '<?php endif; ?>' : '';
                $if_admin_start = "<?php if (\$body['is_admin']): ?>";
                $if_admin_end = "<?php endif; ?>";
                $code .= <<<CHECKBOX_CODE
				
	$if_admin_start
	<div class="row form-inline form-group">
		<label class="col-2 justify-content-end">$legend_2m</label>
		<div class="col-6 form-check form-check-inline justify-content-start">

			<?php foreach (\$body['{$a->type}'] as \${$a->type} ): ?>
				$if_O2M_start
				<div class="form-check form-check-inline">
					<input class="form-check-input" type="checkbox" id="id-{$a->name}-<?=\${$a->type}->id ?>" name="{$a->name}[]" value="<?= \${$a->type}->id ?>" $checked_string>
					<label class="form-check-label" for="id-{$a->name}-<?=\${$a->type}->id?>" ><?= \${$a->type}->$main_attribute ?></label>
				</div>
				$if_O2M_end
			<?php endforeach; ?>
						
		</div>
	</div>
	$if_admin_end


CHECKBOX_CODE;
            }
        }
    }

    return $code;
}

// ------------------------------
function generate_view_create_end($class_name)
{
    $code = <<<HTML

<div class="row offset-2 col-6">
	<input type="submit" class="btn btn-primary" value="Crear">

</form>


<form action="<?=base_url()?>$class_name/list" method="post">
	<input type="hidden" name="filter" value="<?=\$body['filter']?>" />
	<input type="submit" class="offset-1 btn btn-primary" value="Cancelar">
</form>

</div>

</div>	
HTML;
    return $code;
}

// ------------------------------
function generate_view_update_end($class_name)
{
    $code = <<<HTML
<div class="row offset-2 col-6">
	<input type="submit" class="btn btn-primary" value="Actualizar">
</form>


<form action="<?=base_url()?>$class_name/list" method="post">
	<input type="hidden" name="filter" value="<?=\$body['filter']?>" />
	<input type="submit" class="offset-1 btn btn-primary" value="Cancelar">
</form>

</div>

			
HTML;

    return $code;
}

// ------------------------------
function generate_view_create_post($class)
{
    $code = <<<CODE
<?php
// CODIGO de la VISTA {$class->name} CREATE POST AJAX
?>
CODE;
    file_put_contents(APPPATH . 'views' . DIRECTORY_SEPARATOR . $class->name . DIRECTORY_SEPARATOR . 'create_post.php', $code);
}

// ------------------------------
function getMainAttributeName($classes, $class_name)
{
    $name = 'unknown';
    foreach ($classes as $c) {
        if ($c->name == $class_name) {
            $name = $c->getMainAttribute();
        }
    }
    return $name;
}

// ------------------------------
function generate_view_list($class, $classes)
{
    $title = 'LISTA de '; // TODO LOC
    $cn = $class->name;
    $ma = $class->getMainAttribute();
    $code = <<<CODE

<script>
	$(document).ready(function() 
	    { 
	        $("#myTable").tablesorter(); 
	    } 
	);
</script>

<?php error_reporting(0); ?>
<div class="container">
<div class="row">
	<div class="col-4 col-sm-4 col-md-4 col-lg-4 col-xl-4">
		<form id="id-create" class="form-inline"  action="<?=base_url()?>$cn/create">
			<input type="hidden" id="id-createfilter" name="filter" value="" />
			<input type="button" class="btn btn-primary" value="Crear $cn" autofocus="autofocus"
				onclick="getElementById('id-createfilter').value  = getElementById('id-filter').value ;getElementById('id-create').submit() ;">
		</form>
	</div>

	<div class="col-4 col-sm-4 col-md-4 col-lg-4 col-xl-4">
		<form class="form-inline" action="<?=base_url()?>$cn/list" method="post">
			<label for="id-filter">Filtrar</label>
			<input id="id-filter" type="search" name="filter" value="<?=\$body['filter']?>" class="form-control" >
		</form>
	</div>
</div>

<h1>$title $cn</h1>

<table id="myTable" class="table table-hover table-striped tablesorter">
	<thead>
	<tr>
		<th>$ma</th>
CODE;
    foreach ($class->attributes as $a) {
        if (! $a->hidden_recover) {
            if (! $a->main) {
                if (! ($a->is_dependant())) {
                    $code .= '		<th>' . $a->name . '</th>' . PHP_EOL;
                } else {
                    $code .= '		<th>' . $a->name . ' - ' . getMainAttributeName($classes, $a->type) . "({$a->type})</th>" . PHP_EOL;
                }
            }
        }
    }
    $code .= <<<CODE
		<th>Acciones</th>
	</tr>
	</thead>

	<tbody>
	<?php foreach (\$body['$cn'] as \$$cn): ?>
		<tr>
			<td class="alert alert-success"><?= str_ireplace(\$body['filter'], '<kbd>'.\$body['filter'].'</kbd>', \$$cn -> $ma) ?></td>

CODE;
    foreach ($class->attributes as $a) {
        if (! $a->hidden_recover) {
            if (! $a->main) {
                if (! ($a->is_dependant())) {
                    if ($a->type == 'file') { // ============ REGULAR FILE ATTRIBUTE ===============
                        $img_path = "( ( \$$cn -> {$a->name} == null || \$$cn -> {$a->name} == '' ) ? 'assets/img/icons/png/ban-4x.png' : 'assets/upload/'.\$$cn -> {$a->name})";
                        $img_size = "<?=( \$$cn -> {$a->name} == null || \$$cn -> {$a->name} == '' ) ? 15 : 30?>";
                        $code .= "\n\t\t\t<td><img src=\"<?=base_url().$img_path?>\" alt=\"IMG\" width=\"$img_size\" height=\"$img_size\" /></td>" . PHP_EOL;
                    } else { // ================================= REGULAR ATTRIBUTE ====================
                        $td_content = "\$$cn -> {$a->name}";
                        $code .= "\n\t\t\t<td><?= str_ireplace(\$body['filter'], '<kbd>'.\$body['filter'].'</kbd>',$td_content) ?></td>" . PHP_EOL;
                    }
                } else {
                    $main_attribute_name = getMainAttributeName($classes, $a->type);
                    if ($a->mode == 'M2O' || $a->mode == 'O2O') { // ===== SOMETHING TO ONE RELATIONSHIPS =======
                        $td_content = "\$$cn ->  fetchAs('{$a->type}') -> {$a->name} -> {$main_attribute_name}";
                        $code .= "\n\t\t\t<td><?= str_ireplace(\$body['filter'], '<kbd>'.\$body['filter'].'</kbd>',$td_content) ?></td>" . PHP_EOL;
                    } else if ($a->mode == 'O2M') { // ============ ONE TO MANY RELATIONSHIP ====================
                        $capital_type = ucfirst($a->type);

                        $code .= <<<CODE

			<td>
			<?php foreach (\$$cn -> alias ('{$a->name}') -> own{$capital_type}List as \$data): ?>
				<span><?= str_ireplace(\$body['filter'], '<kbd>'.\$body['filter'].'</kbd>', \$data -> $main_attribute_name) ?> </span>
			<?php endforeach; ?>
			</td>

CODE;
                    } else if ($a->mode == 'M2M') { // ============ MANY TO MANY RELATIONSHIP ===============
                        $capital_name = ucfirst($a->name);
                        $code .= <<<CODE
					
			<td>
			<?php foreach (\$$cn -> aggr('own{$capital_name}List', '{$a->type}') as \$data): ?>
				<span><?= str_ireplace(\$body['filter'], '<kbd>'.\$body['filter'].'</kbd>', \$data -> $main_attribute_name ) ?> </span>
			<?php endforeach; ?>
			</td>
				
CODE;
                    }
                }
            }
        }
    }
    $code .= <<<CODE

			<td class="form-inline text-center">

				<form id="id-update-<?= \$$cn -> id ?>" action="<?= base_url() ?>$cn/update" method="post" class="form-group">
					<input type="hidden" name="id" value="<?= \$$cn -> id ?>">
					<input type="hidden" name="filter" value="" id="id-updatefilter-<?= \$$cn -> id ?>">
					<button onclick="getElementById('id-updatefilter-<?= \$$cn -> id ?>').value  = getElementById('id-filter').value ;getElementById('id-update').submit() ;">
						<img src="<?= base_url() ?>assets/img/icons/png/pencil-2x.png" height="15" width="15" alt="editar">
					</button>
				</form>

				<form id="id-delete-<?= \$$cn -> id ?>" action="<?= base_url() ?>$cn/delete" method="post" class="form-group">
					<input type="hidden" name="id" value="<?= \$$cn -> id ?>">
					<input type="hidden" name="filter" value="" id="id-deletefilter-<?= \$$cn -> id ?>">
					<button onclick="getElementById('id-deletefilter-<?= \$$cn -> id ?>').value  = getElementById('id-filter').value ;getElementById('id-delete').submit() ;">
						<img src="<?= base_url() ?>assets/img/icons/png/trash-2x.png" height="15" width="15" alt="borrar">
					</button>
				</form>

			</td>

		</tr>
	<?php endforeach; ?>
	</tbody>
</table>
</div>
<?php error_reporting(E_ALL); ?>
CODE;

    file_put_contents(APPPATH . 'views' . DIRECTORY_SEPARATOR . $class->name . DIRECTORY_SEPARATOR . 'list.php', $code);
}

// ------------------------------
function db_create_set_uniques_and_freeze($classes, $controller)
{
    R::freeze(false);
    foreach ($classes as $class) {
        db_bean_test_create($class);
    }
    foreach ($classes as $c) {
        foreach ($c->attributes as $a) {
            if ($a->unique) {
                $db = $controller->load->database();
                $sql = "ALTER TABLE `{$c->name}` ADD UNIQUE(`{$a->name}`)";
                $controller->db->simple_query($sql);
            }
        }
    }

    R::freeze(true);
}

// ------------------------------

/**
 *
 * @param string $path
 *            the path to file from application folder without the first slash
 */
function file_application_replace($path, $pattern, $replacement)
{
    $app_file = APPPATH . implode(DIRECTORY_SEPARATOR, explode('/', $path));
    $content = file_get_contents($app_file);
    file_put_contents($app_file, preg_replace($pattern, $replacement, $content));
}

// ------------------------------
function db_bean_test_create($class)
{
    $cn = $class->name;

    foreach ($class->attributes as $a) {
        $bean = R::dispense($cn);
        $name = $a->name;
        if (! $a->collection) { // REGULAR ATTRIBUTE
            switch ($a->type) {
                case "String":
                    ;
                case "file":
                    $bean->$name = "TEST";
                    break;
                case "number":
                    $bean->$name = "70000";
                    break;
                case "date":
                    $bean->$name = "1900-12-31";
                    break;
            }
            R::store($bean);
            R::trash($bean);
        } else {
            $type = $a->type;

            if ($a->mode == 'O2O') { // ONE TO ONE

                $o2o = R::dispense($type);

                $bean->$name = $o2o;
                R::store($bean);

                $o2o->$name = $bean;
                R::store($o2o);

                R::trash($bean);

                R::trash($o2o);
            }

            if ($a->mode == 'O2M') { // ONE TO MANY
                R::store($bean);
                $o2m = R::dispense($type);
                $o2m->$name = $bean;
                R::store($o2m);

                R::trash($bean);
                R::trash($o2m);
            }

            if ($a->mode == 'M2M') { // MANY TO MANY
                R::store($bean);
                $another_bean = R::dispense($type);
                R::store($another_bean);
                $m2m = R::dispense($name);
                $m2m->$cn = $bean;
                $m2m->$type = $another_bean;
                R::store($m2m);

                R::trash($m2m);
                R::trash($bean);
                R::trash($another_bean);
            }
        }
    }
}

// ------------------------------

?>