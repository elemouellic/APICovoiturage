<?php

namespace App\Controller;

use App\Entity\Car;
use App\Entity\City;
use App\Entity\Student;
use App\Utils\Utils;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Class StudentController
 * @package App\Controller
 */
#[Route('/api')]
class StudentController extends AbstractController
{

    private TokenStorageInterface $tokenStorage;
    private JWTTokenManagerInterface $JWTManager;

    /**
     * StudentController constructor.
     * @param TokenStorageInterface $tokenStorage
     * @param JWTTokenManagerInterface $JWTManager
     */
    public function __construct(TokenStorageInterface $tokenStorage, JWTTokenManagerInterface $JWTManager)
    {
        $this->tokenStorage = $tokenStorage;
        $this->JWTManager = $JWTManager;
    }

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
     * Delete a student
     * @param Request $request The request object
     * @param EntityManagerInterface $em The entity manager
     * @return JsonResponse The response
     */
    #[Route('/deletepersonne/{id}', name: 'app_student_delete', methods: ['DELETE'])]
    public function deleteStudent(Request $request, EntityManagerInterface $em): JsonResponse
    {
        // Get the student from the database
        $id = $request->get('id');
        $student = $em->getRepository(Student::class)->find($id);
        if (!$student) {
            throw new HttpException(404, 'Student not found');
        }

        // Check if the student is an admin
        if(in_array('ROLE_ADMIN', $student->getRegister()->getRoles())){
            throw new HttpException(403, 'You are not allowed to delete an admin');
        }

        // Get the user associated with the student
        $user = $student->getRegister();

        $em->remove($student);
        $em->remove($user);
        $em->flush();
        return $this->json([
            'message' => 'Student deleted successfully',
        ]);
    }

    /**
     * Get a student by id
     * @param Request $request The request object
     * @param EntityManagerInterface $em The entity manager
     * @return JsonResponse The response
     */
    #[Route('/selectpersonne/{id}', name: 'app_student_get', methods: ['GET'])]
    public function getStudent(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $id = $request->get('id');
        $student = $em->getRepository(Student::class)->find($id);
        if (!$student) {
            throw new HttpException(404, 'Student not found');
        }
        return $this->json([
            'id' => $student->getId(),
            'firstname' => $student->getFirstname(),
            'name' => $student->getName(),
            'phone' => $student->getPhone(),
            'email' => $student->getEmail(),
            'city' => $student->getLive()->getName(),
            'car' => $student->getPossess() ? $student->getPossess()->getModel() : null,
        ]);
    }

    /**
     * List all the students
     * @param EntityManagerInterface $em The entity manager
     * @return JsonResponse The response
     */
    #[Route('/listpersonne', name: 'app_student_list', methods: ['GET'])]
    public function listAllStudents(EntityManagerInterface $em): JsonResponse
    {
        $students = $em->getRepository(Student::class)->findAll();
        $data = [];
        foreach ($students as $student) {
            $data[] = [
                'id' => $student->getId(),
                'firstname' => $student->getFirstname(),
                'name' => $student->getName(),
                'phone' => $student->getPhone(),
                'email' => $student->getEmail(),
                'city' => $student->getLive()->getName(),
                'car' => $student->getPossess() ? $student->getPossess()->getModel() : null,
            ];
        }
        return $this->json($data);
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
