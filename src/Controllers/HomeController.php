<?php
namespace App\Controllers;

use App\Datatables\DataBaseProcessing;
use App\Validators\RespectValidator;
use PDO;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Respect\Validation\Validator as Validator;
use Slim\Flash\Messages;
use Slim\Views\PhpRenderer;

class HomeController
{
    protected $container;
    protected $db;
    protected $flash;
    protected $renderer;
    protected $validator;

    // constructor receives container instance
    public function __construct(ContainerInterface $container) {
        $this->container = $container;
        $this->db = $this->container->get(PDO::class);
        $this->flash = $this->container->get(Messages::class);
        $this->renderer = $this->container->get(PhpRenderer::class);
        $this->validator = $this->container->get(RespectValidator::class);
    }

    public function home(Request $request, Response $response, $args) {
        $args = $this->setArgs($request, $args);
        $args['nav']['home'] = 'active';
        $args['nav']['about'] = '';
        return $this->renderer->render($response, 'index.phtml', $args);
    }

    public function about(Request $request, Response $response, $args) {
        $args = $this->setArgs($request, $args);
        $args['nav']['home'] = '';
        $args['nav']['about'] = 'active';
        return $this->renderer->render($response, 'about.phtml', $args);
    }

    public function login(Request $request, Response $response, $args) {
        $validation = $this->validator->validate($request, [
            'username' => Validator::noWhitespace()->notEmpty(),
            'password' => Validator::noWhitespace()->notEmpty()
        ]);
        if ($validation->failed()) {
            $this->flash->addMessage('errors', $validation->getErrors());
            $this->flash->addMessage('inputs', $validation->getInputs());
        } else {
            $data = $request->getParsedBody();
            $inputs['username'] = $data['username'];
            $inputs['password'] = $data['password'];
            $data['password'] = md5($data['password']);
            $columns = array();
            $counter = 0;
            foreach ($this->fieldData('psa_users') as $field) {
                $columns[] = array('db' => $field['name'], 'dt' => $counter);
                $counter++;
            }
            $result = DataBaseProcessing::select((object) $data, $this->db, 'psa_users', $columns);
            if (count($result) > 0) {
                $_SESSION['username'] = $result[0]['username'];
                $_SESSION['tablename'] = $result[0]['tablename'];
            } else {
                $errors['login'] = "User authentication failed.";
                $this->flash->addMessage('errors', $errors);
                $this->flash->addMessage("inputs", $inputs);
            }
        }
        return $response->withHeader('Location', '/')->withStatus(302);
    }

    public function logout(Request $request, Response $response, $args) {
        session_unset();
        return $response->withHeader('Location', '/')->withStatus(302);
    }

    private function fieldData($tablename) {
        $fields = array();
        $sql = "PRAGMA table_info(" . $tablename . ");";
        $result = $this->db->query($sql);
        if ($result) {
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                $fields[] = $row;
            }
        }
        return $fields;
    }

    /**
     * @param Request $request
     * @param $args
     * @return mixed
     */
    public function setArgs(Request $request, $args)
    {
        $session = $request->getAttribute('session');
        $args['username'] = isset($session['username']) ? $session['username'] : '';
        $args['tablename'] = isset($session['tablename']) ? $session['tablename'] : 'psa_demo';
        $args['menu'] = $request->getAttribute('menu');
        $args['version'] = $this->db->getAttribute(PDO::ATTR_SERVER_VERSION);
        $args['tables'] = DataBaseProcessing::list($this->db);
        $args['fields'] = $this->fieldData($args['tablename']);
        $args['errors'] = $this->flash->getMessage('errors');
        $args['inputs'] = $this->flash->getMessage('inputs');
        return $args;
    }
}