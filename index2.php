<?php
// Carpeta: api/controllers/PatientController.php

// Encabezados para permitir el acceso CORS y el formato JSON
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Incluir archivos de conexión y modelo
include_once '../config/Database.php';
include_once '../models/Patient.php';

$database = new Database();
$db = $database->getConnection();

$patient = new Patient($db);

$request_method = $_SERVER["REQUEST_METHOD"];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri_segments = explode('/', $uri);

switch($request_method) {
    case 'GET':
        // Si hay un ID en la URL, leer un solo paciente
        if (isset($uri_segments[count($uri_segments) - 1]) && is_numeric($uri_segments[count($uri_segments) - 1])) {
            $patient->id = $uri_segments[count($uri_segments) - 1];
            $patient->readOne();
            if ($patient->name != null) {
                $patient_arr = array(
                    "id" => $patient->id,
                    "name" => $patient->name,
                    "email" => $patient->email,
                    "phone" => $patient->phone
                );
                http_response_code(200);
                echo json_encode($patient_arr);
            } else {
                http_response_code(404);
                echo json_encode(array("message" => "Paciente no encontrado."));
            }
        } else {
            // Leer todos los pacientes
            $stmt = $patient->read();
            $num = $stmt->rowCount();
            if ($num > 0) {
                $patients_arr = array();
                $patients_arr["records"] = array();
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    extract($row);
                    $patient_item = array(
                        "id" => $id,
                        "name" => $name,
                        "email" => $email,
                        "phone" => $phone
                    );
                    array_push($patients_arr["records"], $patient_item);
                }
                http_response_code(200);
                echo json_encode($patients_arr);
            } else {
                http_response_code(404);
                echo json_encode(array("message" => "No se encontraron pacientes."));
            }
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"));
        // Validación del lado del servidor
        if (!empty($data->name) && !empty($data->email) && filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
            $patient->name = $data->name;
            $patient->email = $data->email;
            $patient->phone = $data->phone;
            if ($patient->create()) {
                http_response_code(201);
                echo json_encode(array("message" => "Paciente creado correctamente."));
            } else {
                http_response_code(503);
                echo json_encode(array("message" => "No se pudo crear el paciente."));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Datos incompletos o inválidos."));
        }
        break;

    case 'PUT':
        $data = json_decode(file_get_contents("php://input"));
        // Se espera el ID en el cuerpo de la solicitud para la actualización
        if (empty($data->id)) {
            http_response_code(400);
            echo json_encode(array("message" => "Falta el ID del paciente."));
            return;
        }

        $patient->id = $data->id;
        $patient->name = $data->name;
        $patient->email = $data->email;
        $patient->phone = $data->phone;

        if ($patient->update()) {
            http_response_code(200);
            echo json_encode(array("message" => "Paciente actualizado correctamente."));
        } else {
            http_response_code(503);
            echo json_encode(array("message" => "No se pudo actualizar el paciente."));
        }
        break;

    case 'DELETE':
        $data = json_decode(file_get_contents("php://input"));
        if (empty($data->id)) {
            http_response_code(400);
            echo json_encode(array("message" => "Falta el ID del paciente."));
            return;
        }

        $patient->id = $data->id;

        if ($patient->delete()) {
            http_response_code(200);
            echo json_encode(array("message" => "Paciente eliminado correctamente."));
        } else {
            http_response_code(503);
            echo json_encode(array("message" => "No se pudo eliminar el paciente."));
        }
        break;

    default:
        // Método no soportado
        http_response_code(405);
        echo json_encode(array("message" => "Método no permitido.")
