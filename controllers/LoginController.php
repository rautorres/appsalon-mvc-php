<?php 

namespace Controllers;

use MVC\Router;
use Model\Usuario;
use Classes\Email;


class LoginController {
    public static function login(Router $router) {
        $alertas = [];

        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            $auth = new Usuario($_POST);

            $auth->validarLogin();
            $alertas = $auth->validarLogin();

            if(empty($alertas)) {
                //Comprobra que exista el usuario
                $usuario = Usuario::where('email', $auth->email);

                if($usuario) {
                    //Verificar el password
                    if( $usuario->comprobarPasswordAndVerificado($auth->password)) {
                        //AUTENTICAr EL USUARIO
                        //session_start();
                        $_SESSION['id'] = $usuario->id;
                        $_SESSION['nombre'] = $usuario->nombre . " " . $usuario->apellido;
                        $_SESSION['email'] = $usuario->email;
                        $_SESSION['login'] = true;

                        //REDIRECCIONAMIENTO ADMIN
                        if($usuario->admin == 1) {
                            $_SESSION['admin'] = (int) $usuario->admin;
                            header('Location: /admin');
                            exit;
                        } else {
                            header('Location: /cita');
                            exit;
                        }
                    }
                } else {
                    Usuario::setAlerta('error', 'Usuario no encontrado');
                }
            }
        }

        $alertas = Usuario::getAlertas();
        
        $router->render('auth/login', [
            'alertas' => $alertas
        ]);
    }

    public static function logout () {
        //session_start();

        $_SESSION = [];

        header('Location: /');
}

    public static function olvide (Router $router) {
        $alertas = [];

        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            $auth = new Usuario($_POST);
            $alertas = $auth->validarEmail();

            if(empty($alertas)) {
                $usuario = Usuario::where('email', $auth->email);

                if($usuario && $usuario->confirmado === "1") {
                    //GENERAR TOKEN
                    $usuario->crearToken();
                    $usuario->guardar();

                    //Envia el email
                    $email = new Email($usuario->email, $usuario->nombre, $usuario->token);
                    $email->enviarInstrucciones();

                    //Alerta de éxito
                    Usuario::setAlerta('exito', 'Revisa tu Email');

                } else {
                    Usuario::setAlerta('error', 'El Usuario no existe o no está confirmado');
                }
            }
        }
    
        $alertas = Usuario::getAlertas();


        $router->render('auth/olvide-password', [
            'alertas' => $alertas
        ]);
    }

    public static function recuperar(Router $router) {
        $alertas = [];
        $error= false;

        $token = s($_GET['token']);

        //BUSCAR USUARIO POR SU TOKEN
        $usuario = Usuario::where('token', $token);

        if(empty($usuario)) {
            Usuario::setAlerta('error', 'Token No Válido');
            $error = true;
        }

        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            //Leer el nuevo password y guardarlo
            $password = new Usuario($_POST);
            $alertas = $password->validarPassword();

            if(empty($alertas)) {
                $usuario->password = null;
                $usuario->password = $password->password;
                $usuario->hashPassword();
                $usuario->token = null;

                $resultado = $usuario->guardar();
                if($resultado) {
                    header('Location: /');
                }
            }
        }

        $alertas = Usuario::getAlertas();
        $router->render('auth/recuperar-password', [
            'alertas' =>$alertas,
            'error' => $error
        ]);
    }

    public static function crear (Router $router) {
        $usuario = new \Model\Usuario($_POST);

        //ALERTAS VACIAS
        $alertas = [];

        if ($_SERVER['REQUEST_METHOD']=== 'POST') {
            $usuario->sincronizar ($_POST);
            $alertas = $usuario->validarNuevaCuenta();

            //REVISAR QUE ALERTAS ESTÉ VACIO
            if(empty($alertas)) {
                //VERIFICAR QUE EL USUARIO NO ESTÉ REGISTRADO
                $usuario->existeUsuario();

                //OBTENER ALERTAS ACTUALIZADAS
                $alertas = Usuario::getAlertas();
                
                if(empty($alertas)){
                    //HASH
                    $usuario->hashPassword();

                    //GENERAR TOKEN UNICO
                    $usuario->crearToken();

                    //ENVIAR EMAIL
                    $email = new Email($usuario->nombre, $usuario->email, $usuario->token);
                    $email->enviarConfirmacion();

                    //CREAR EL USUARIO
                    $resultado = $usuario->guardar();
                    if($resultado) {
                        header('Location: /mensaje');
                    }

                    //debuguear($usuario);
                } 
            }
        }

        $router->render('auth/crear-cuenta', [ 
            'usuario' => $usuario,
            'alertas' => $alertas
        ]);
    }

    public static function mensaje(Router $router) {
        $router->render('auth/mensaje');
    }

    public static function confirmar(Router $router) {
        $alertas = [];
        $token = s($_GET['token']);
        $usuario = Usuario::where('token', $token);

        if(empty($usuario)) {
            //Mostrar mensaje de error
            Usuario::setAlerta('error', 'Token No Válido');
        } else {
            //Modificar a usuario confirmado
            $usuario->confirmado = "1";
            $usuario->token = null;
            $usuario->guardar();
            Usuario::setAlerta('exito', 'Cuenta Comprobada Correctamente');
        }
        //Obtener alertas
        $alertas = Usuario::getAlertas();

        //Renderizar la vista
        $router->render('auth/confirmar-cuenta' , [
            'alertas' => $alertas
        ]);
    }
}