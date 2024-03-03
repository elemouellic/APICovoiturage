<?php

namespace App\Controller;

use App\Entity\User;
use App\Utils\Utils;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class UserController extends AbstractController
{

    // Attributes to hash the password and generate the JWT token
    private UserPasswordHasherInterface $passwordHasher;
    private JWTTokenManagerInterface $JWTManager;

    // Constructor to inject the password hasher and the JWT token manager
    public function __construct(UserPasswordHasherInterface $passwordHasher, JWTTokenManagerInterface $JWTManager)
    {
        $this->passwordHasher = $passwordHasher;
        $this->JWTManager = $JWTManager;
    }

    /**
     * @throws Exception
     */
    #[Route('/register', name: 'app_user_add', methods: ['POST'])]
    public function register(Request $request, EntityManagerInterface $em): JsonResponse
    {
        // Serialize the request data
        $data = Utils::serializeRequestData($request);

        if (!isset($data['login']) || !isset($data['password']) || $data['login'] === '' || $data['password'] === '' || strlen($data['login']) < 8 || strlen($data['password']) < 8) {
            throw new HttpException(400, 'Login and password must be at least 8 characters long.');
        }

        try {
            // Create a new user
            $user = new User();
            // Set the user login and password
            $user->setLogin($data['login']);
            $user->setPassword($this->passwordHasher->hashPassword(
                $user,
                $data['password']
            ));

            // Set the user role
            $user->setRoles(['ROLE_USER']);

            // Persist the user
            $em->persist($user);
            $em->flush();

            // Generate a random token for the user
            $token = $this->JWTManager->create($user);

            $result = [
                'id' => $user->getId(),
                'login' => $user->getLogin(),
                'roles' => $user->getRoles(),
                'token' => $token,
            ];
        } catch (Exception) {
            throw new HttpException(400, 'An error occurred while registering the user.');
        }

        // Return the user data with a 201 status code (created)
        return $this->json($result, 201);

    }

#[Route('/login', name: 'app_login', methods: ['POST'])]
public function login(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher, JWTTokenManagerInterface $JWTManager): JsonResponse
{
    // Serialize the request data
    $data = Utils::serializeRequestData($request);

    // Check if login and password are set
    if (!isset($data['login']) || !isset($data['password'])) {
        throw new HttpException(400, 'Login and password are required.');
    }

    // Retrieve the user from the database
    $user = $em->getRepository(User::class)->findOneBy(['login' => $data['login']]);

    // Check if user exists and the password is valid
    if (!$user || !$passwordHasher->isPasswordValid($user, $data['password'])) {
        throw new HttpException(401, 'Invalid credentials.');
    }

    // Generate a JWT token for the user
    $token = $JWTManager->create($user);

    // Return the token and user data
    return $this->json([
        'token' => $token,
        'user' => [
            'id' => $user->getId(),
            'login' => $user->getLogin(),
            'roles' => $user->getRoles(),
        ],
    ]);
}



}
