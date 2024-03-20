<?php

namespace App\Controller;

use App\Entity\Brand;
use App\Entity\Car;
use App\Entity\City;
use App\Entity\Student;
use App\Entity\User;
use App\Utils\Utils;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

#[Route('/api')]
class StudentController extends AbstractController
{

    private TokenStorageInterface $tokenStorage;
    private JWTTokenManagerInterface $JWTManager;

    // Constructor to inject the token storage
    public function __construct(TokenStorageInterface $tokenStorage, JWTTokenManagerInterface $JWTManager)
    {
        $this->tokenStorage = $tokenStorage;
        $this->JWTManager = $JWTManager;
    }



//#[Route('/testtoken', name: 'app_test_token', methods: ['GET'])]
//public function testToken(): JsonResponse
//{
//    // Retrieve the token from the request
//    $token = $this->JWTManager->getUserIdClaim();
//    $storage = $this->tokenStorage->getToken();
//
//
//    // Check if the token exists
//    if ($token) {
//        $jwtToken = $storage->getCredentials();
//            return new JsonResponse(['message' => 'Valid token', $jwtToken], 200);
//    } else {
//        return new JsonResponse(['message' => 'No token'], 401);
//    }
//}

    /**
     * Insert a new student
     * @param Request $request The request object
     * @param EntityManagerInterface $em The entity manager
     * @return JsonResponse The response
     */
    #[Route('/insertpersonne', name: 'app_student_insert', methods: ['POST'])]
    public function insertStudent(Request $request, EntityManagerInterface $em): JsonResponse
    {
        // Serialize the request data
        $data = Utils::serializeRequestData($request);

        // Check if all necessary fields are present and not empty
        if (empty($data['firstname']) || empty($data['name']) || empty($data['phone']) || empty($data['email']) || empty($data['cityId'])) {
            throw new HttpException(400, 'Missing required fields.');
        } else {
            // Optional car possession
            $carId = $data['carId'] ?? null;

            // Create a new student
            $student = $this->createStudent($data['firstname'], $data['name'], $data['phone'], $data['email'], $data['cityId'], $carId, $em);

            // Return the created student data
            return $this->json([
                'id' => $student->getId(),
                'firstname' => $student->getFirstname(),
                'name' => $student->getName(),
                'phone' => $student->getPhone(),
                'email' => $student->getEmail(),
                'city' => $student->getLive()->getName(),
                'car' => $student->getPossess()?->getModel(),
            ], 201);
        }
    }

    /**
     * Update a student
     * @param Request $request The request object
     * @param EntityManagerInterface $em The entity manager
     * @return JsonResponse The response
     */
    #[Route('/updatepersonne', name: 'app_student_update', methods: ['PUT'])]
    public function updateStudent(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = Utils::serializeRequestData($request);

        if (empty($data['id']) || empty($data['firstname']) || empty($data['name']) || empty($data['phone']) || empty($data['email']) || empty($data['cityId'])) {
            throw new HttpException(400, 'Missing required fields.');
        } else {
            $student = $em->getRepository(Student::class)->find($data['id']);
            if (!$student) {
                throw new HttpException(404, 'Student not found');
            }
            $student->setFirstname($data['firstname']);
            $student->setName($data['name']);
            $student->setPhone($data['phone']);
            $student->setEmail($data['email']);
            $city = $em->getRepository(City::class)->find($data['cityId']);
            if (!$city) {
                throw new HttpException(404, 'City not found');
            }
            $student->setLive($city);

            if (!empty($data['carId'])) {
                $car = $em->getRepository(Car::class)->find($data['carId']);
                if (!$car) {
                    throw new HttpException(404, 'Car not found');
                }
                $student->setPossess($car);
            } else {
                $student->setPossess(null);
            }

            $em->persist($student);
            $em->flush();
            return $this->json([
                'id' => $student->getId(),
                'firstname' => $student->getFirstname(),
                'name' => $student->getName(),
                'phone' => $student->getPhone(),
                'email' => $student->getEmail(),
                'city' => $student->getLive()->getName(),
                'car' => $student->getPossess() ? $student->getPossess()->getModel() : null,
            ], 200);
        }
    }

    /**
     * Create a new Student
     * @param string $firstname The firstname
     * @param string $name The name
     * @param string $phone The phone
     * @param string $email The email
     * @param EntityManagerInterface $em The entity manager
     * @return Student The created Student
     */
    private function createStudent(string $firstname, string $name, string $phone, string $email, int $cityId, ?int $carId, EntityManagerInterface $em): Student
    {
        // Get the user from the token
        $user = $this->tokenStorage->getToken()->getUser();
        // Check if the student already exists, phone and email must be unique
        $this->checkUniquePhoneAndEmail($phone, $email, $em);

        $city = $em->getRepository(City::class)->find($cityId);
        if (!$city) {
            throw new HttpException(404, 'City not found');
        }

        // A student does not have to possess a car
        $car = null;
        if ($carId !== null) {
            $car = $em->getRepository(Car::class)->find($carId);
            if (!$car) {
                throw new HttpException(404, 'Car not found');
            }
        }

        // Create a new Student
        try {
            $student = new Student();

            // Set the Student attributes
            $student->setFirstname($firstname);
            $student->setName($name);
            $student->setPhone($phone);
            $student->setEmail($email);

            $student->setLive($city);
            $student->setPossess($car);

            // Set the user as the register
            $student->setRegister($user);

            // Persist the Student
            $em->persist($student);
            $em->flush();
        } catch (Exception $e) {
            throw new HttpException(400, $e->getMessage());
        }

        // Return the created Student
        return $student;
    }

    /**
     * Check if a student with the same phone number or email already exists
     * @param string $phone The phone number
     * @param string $email The email
     * @param EntityManagerInterface $em The entity manager
     * @return void
     */
    private function checkUniquePhoneAndEmail(string $phone, string $email, EntityManagerInterface $em): void
    {
        $existingPhone = $em->getRepository(Student::class)->findOneBy(['phone' => $phone]);
        $existingEmail = $em->getRepository(Student::class)->findOneBy(['email' => $email]);
        if ($existingPhone) {
            throw new HttpException(409, 'A student with the same phone number already exist.');
        }
        if ($existingEmail) {
            throw new HttpException(409, 'A student with the same email already exist.');
        }
    }


}
