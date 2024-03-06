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

    #[Route('/insertpersonne', name: 'app_student_insert', methods: ['POST'])]
    public function insertPersonne(Request $request, EntityManagerInterface $em): JsonResponse
    {
        // Retrieve the token from the TokenStorage
        try {
            $token = $this->tokenStorage->getToken();
            // Check if the token exists
            if ($token) {
                // Get the user from the token
                $userFromToken = $token->getUser();

                // Get the JWT token of the user
                $jwtToken = $token->getCredentials();
            }
        } catch (Exception $e) {
            throw new HttpException(401, 'Invalid credentials.');
        }

        // Serialize the request data
        $data = Utils::serializeRequestData($request);

        // Check if all necessary fields are present and not empty
        if (empty($data['firstname']) || empty($data['name']) || empty($data['phone']) || empty($data['email'])) {
            throw new HttpException(400, 'Missing required fields.');
        } else {
//            // Get or create the city
//            $city = $this->getOrCreateCity($em, $data);
//
//            // Get or create the car
//            $car = $this->getOrCreateCar($em, $data);

            // Create a new student
            $student = $this->createStudent($data['firstname'], $data['name'], $data['phone'], $data['email'], $em);

            // Return the created student data
            return $this->json([
                'id' => $student->getId(),
                'firstname' => $student->getFirstname(),
                'name' => $student->getName(),
                'phone' => $student->getPhone(),
                'email' => $student->getEmail(),
            ], 201);
        }
    }

    private function createStudent(string $firstname, string $name, string $phone, string $email, EntityManagerInterface $em): Student
    {
        // Get the user from the token
        $user = $this->tokenStorage->getToken()->getUser();
        // Check if the student already exists, phone and email must be unique
        $this->checkUniquePhoneAndEmail($phone, $email, $em);

        // Create a new Student
        try {
            $student = new Student();

            // Set the Student attributes
            $student->setFirstname($firstname);
            $student->setName($name);
            $student->setPhone($phone);
            $student->setEmail($email);

            // Set the user as the register
            $student->setRegister($user);

            // Persist the Student
            $em->persist($student);
            $em->flush();
        } catch (Exception $e) {
            throw new HttpException(400, 'An error occurred while creating the student.');
        }

        // Return the created Student
        return $student;
    }

    /**
     * Private function to manage violations of unique constraints
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

//    private function checkUniqueCar(string $matriculation, EntityManagerInterface $em): void
//    {
//        $existingMatriculation = $em->getRepository(Car::class)->findOneBy(['matriculation' => $matriculation]);
//        if ($existingMatriculation) {
//            throw new HttpException(409, 'A car with the same matriculation already exist.');
//        }
//    }
//
//
//    private function getOrCreateCity(EntityManagerInterface $em, array $data): City
//    {
//        // Get the city from the database
//        $city = $em->getRepository(City::class)->findOneBy(['name' => $data['city']]);
//
//        if (!$city) {
//            $city = new City();
//            $city->setName($data['city']);
//            if (isset($data['zipcode'])) {
//                $city->setZipCode($data['zipcode']);
//            } else {
//                throw new HttpException(400, 'Missing required fields.');
//            }
//            $em->persist($city);
//            $em->flush();
//        }
//
//        return $city;
//    }
//
//    private function getOrCreateCar(EntityManagerInterface $em, array $data): Car
//    {
//        try {
//            $this->checkUniqueCar($data['matriculation'], $em);
//
//            // Get the car from the database
//            $car = $em->getRepository(Car::class)->findOneBy(['model' => $data['car']]);
//
//            if (!$car) {
//                $car = new Car();
//                $car->setModel($data['model']);
//                $car->setMatriculation($data['matriculation']);
//                $car->setPlaces($data['places']);
//                // Check if the brand field is present and create a new Brand entity if it does not exist
//                if (isset($data['brand'])) {
//                    $brand = $em->getRepository(Brand::class)->findOneBy(['carBrand' => $data['brand']]);
//                    if (!$brand) {
//                        try {
//                            $brand = new Brand();
//                            $brand->setBrand($data['brand']);
//                            $em->persist($brand);
//                            $em->flush();
//                        } catch (Exception $e){
//                            throw new HttpException(404, "titi");
//                        }
//                    }
//                    $car->setIdentify($brand);
//                }
//                $em->persist($car);
//                $em->flush();
//            }
//            return $car;
//        } catch (Exception $e){
//            throw new HttpException(404, "toto");
//        }
//
//    }

}
