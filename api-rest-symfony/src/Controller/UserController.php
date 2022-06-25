<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints\Email;
use App\Entity\User;
use App\Entity\Video;
use App\Services\JwtAuth;


class UserController extends AbstractController
{
    
    private function resjson($data){
        // Serializar datos con servicios de serializer
        $json = $this->get('serializer')->serialize($data, 'json');

        // Response con http-foundation
        $response = new Response();


        // Asignar contenido a la respuesta
        $response->setContent($json);

        //Indicar formato de la respuesta
        $response->headers->set('Content-Type', 'application/json');

        //Devolver respuesta
        return $response;
    }

    public function index(): Response
    {
        $user_repo = $this->getDoctrine()->getRepository(User::class);
        $video_repo = $this->getDoctrine()->getRepository(Video::class);

        $users = $user_repo->findAll();
        $user = $user_repo->find(1);

        $videos = $video_repo->findAll();
        /*foreach($users as $user){
            echo "<h1>{$user->getName()} {$user->getSurname()} </h1>";

            foreach($user->getVideos() as $video){
                echo "<p>{$video->getTitle()} - {$video->getUser()->getEmail()}</p>";
            }
        }

        die();*/
        
        return $this->json($user);
    }

    public function create(Request $request){
        // Recoger datos por post
        $json = $request->get('json', null);

        //Decodificar el json
        //$params = json_decode($json, true);
        $params = json_decode($json);
        // Respuesta por defecto
        $data = [
            'status' => 'error',
            'code' => 200,
            'message' => 'El usuario no se ha creado'
        ];


        //Comprobar y validar datos
        if($json != null){
            $name = (!empty($params->name)) ? $params->name : null;
            $surname = (!empty($params->surname)) ? $params->surname : null;
            $email = (!empty($params->email)) ? $params->email : null;
            $password = (!empty($params->password)) ? $params->password : null;

            $validator = Validation::createValidator();
            $validate_email = $validator->validate($email, [
                new Email()
            ]);

            if(!empty($email) && count($validate_email) == 0 && !empty($password) && !empty($name) && !empty($surname) ){
                /*$data = [
                    'status' => 'success',
                    'code' => 200,
                    'message' => 'El usuario se ha creado'
                ];  */

                //Si la validacion es correcta , crear objeto de usuario
                $user = new User();
                $user->setName($name);
                $user->setSurname($surname);
                $user->setEmail($email);
                $user->setRole("ROLE_USER");
                $user->setCreatedAt(new \Datetime('now'));
                

                //Cifrar contraseña
                $pwd = hash('sha256', $password);
                $user->setPassword($pwd);

                

                //Comprobar si el usuario existe(duplicados)
                $doctrine = $this->getDoctrine();
                $em = $doctrine->getManager();

                $user_repo = $doctrine->getRepository(User::class);
                $isset_user = $user_repo->findBy(array(
                    'email' => $email
                ));

                if(count($isset_user) == 0){
                    // Guardar usuario
                    $em->persist($user);
                    $em->flush();

                    $data = [
                        'status' => 'success',
                        'code' => 200,
                        'message' => 'El usuario se ha creado',
                        'user' => $user
                    ];
                }else{
                    $data = [
                        'status' => 'error',
                        'code' => 200,
                        'message' => 'El usuario ya existe'
                    ];  
                }


                // Si no existe, guardarlo en la db

            }

        }

        

        //Hacer respuesta en json
        return $this->resjson($data);
        //return new JsonResponse($data);
    }

    public function login(Request $request, JwtAuth $jwt_auth){
        // Recibir los datos por post
        $json = $request->get('json',null);
        $params = json_decode($json);

        // Array por defecto a devolver
        $data = [
            'status' => 'error',
            'code' => 200,
            'message' => 'El usuario no se ha podido identificar'
        ];


        // Comprobar y validar datos
        if($json != null){

            $email = (!empty($params->email)) ?  $params->email : null;
            $password = (!empty($params->password)) ?  $params->password : null;
            $gettoken = (!empty($params->gettoken)) ?  $params->gettoken : null;

            $validator = Validation::createValidator();
            $validate_email = $validator->validate($email,[
                new Email()
            ]);

            if(!empty($email) && !empty($password) && count($validate_email) == 0){

                // Cifrar contraseña
                $pwd = hash('sha256', $password);


                // Si todo es valido, llamaremos al servicio para identificar al usuario (jwt, token o un objeto)

               if($gettoken){
                $signup = $jwt_auth->signup($email, $pwd, $gettoken);
               }else{
                $signup = $jwt_auth->signup($email, $pwd);
               }
               
               return new JsonResponse($signup);

            }
        }

       

         // Si los datos estan ok, devolvwr respuesta
        return $this->resjson($data);
    }

    public function edit(Request $request, JwtAuth $jwt_auth){
        // Recoger cabecera de autenticacion
        $token = $request->headers->get('Authorization');

        // Crear metodo para comprobar si el token es correcto
        $authCheck = $jwt_auth->checkToken($token);

        // Respuesta por defecto
        $data = [
            'status' => 'error',
            'code' => 400,
            'message' => 'Usuario no actualizado'
        ];

        // Si es correcto hacer la actualizacion del usuario
        if($authCheck){
            //Actualizar usuario

            // Conseguir entity manager
            $em = $this->getDoctrine()->getManager();

            // Conseguir datos de usuario identificado
            $identity = $jwt_auth->checkToken($token, true);

            //Conseguir el usuario a actualizar
            $user_repo = $this->getDoctrine()->getRepository(User::class);
            $user = $user_repo->findOneBy([
                'id' => $identity->sub
            ]);

            // Recoger datos por post
            $json = $request->get('json', null);
            $params = json_decode($json);

            // Comprobar y validar datos
            if(!empty($json)){
                $name = (!empty($params->name)) ? $params->name : null;
                $surname = (!empty($params->surname)) ? $params->surname : null;
                $email = (!empty($params->email)) ? $params->email : null;
                

                $validator = Validation::createValidator();
                $validate_email = $validator->validate($email, [
                    new Email()
                ]);

                if(!empty($email) && count($validate_email) == 0  && !empty($name) && !empty($surname)){
                    // Asignar nuevos datos al objeto
                    $user->setEmail($email);
                    $user->setName($name);
                    $user->setSurname($surname);

                    // Comprobar duplicados
                    $isset_user = $user_repo->findBy([
                        'email' => $email
                    ]);

                    if(count($isset_user) == 0 || $identity->email == $email){
                        // Guardar cambios en la db
                        $em->persist($user);
                        $em->flush();

                        $data = [
                            'status' => 'success',
                            'code' => 200,
                            'message' => 'Usuario actualizado',
                            'user' => $user
                        ];

                    }else{
                        $data = [
                            'status' => 'error',
                            'code' => 400,
                            'message' => 'Email invalido'
                        ];
                    }

                    
                }
            }

            
        }


        

        return $this->resjson($data);

    }
}
