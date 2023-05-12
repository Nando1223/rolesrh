<?php

require('fpdf/fpdf.php');


function Crear_PDF($ID_rol)

{
    include('conexion.php');


    try {

        $pdo = new PDO("sqlsrv:server=$sql_serverName;Database=$sql_database", $sql_user, $sql_pwd);

        $result = $pdo->prepare("select Detalle as Concepto , ID , Fecha  , Ingresos as Ingreso_Total , Egresos as Egreso_Total , Total as Valor_Neto from EMP_ROLES 
    where ID = '0000034709'");

        // $result->bindParam(':ID_Rol', $ID_rol);
        $result->execute();
        $data = $result->fetchAll(PDO::FETCH_ASSOC);


        $result2 = $pdo->prepare("

        SELECT
        rr.DocumentoID,
        rr.Tipo as Clase,
        rub.Nombre as Detalle,
        CASE WHEN rr.Tipo = 'Ingreso' THEN rr.Calculado ELSE 0 END as Ingreso,
        CASE WHEN rr.Tipo = 'Egreso' THEN rr.Valor ELSE 0 END as Egreso,
        CASE WHEN rr.DocumentoID = '' THEN rub.Nombre ELSE de.Detalle END as Detalle,
        ISNULL(de.Tipo,'') as Tipo ,
        de.DocumentoID as Referencia
        FROM EMP_ROLES_RUBROS rr
        JOIN EMP_RUBROS rub ON rr.RubroID = rub.ID
        LEFT JOIN EMP_EMPLEADOS_DEUDAS de ON de.ID = rr.DocumentoID
        WHERE rr.RolID = '0000034709' AND rr.Tipo <> 'Provision'
        ORDER BY LEN(Detalle) ASC, Ingreso DESC, Egreso ASC");


        // $result->bindParam(':ID_Rol', $ID_rol);
        $result2->execute();
        $cuerpo = $result2->fetchAll(PDO::FETCH_ASSOC);

        $pdf = new FPDF();
        $pdf->AddPage();

        // Celda de Cabecera 

        foreach ($data as $row) {

            // Establecer posición actual en la esquina superior Izquierda 
            // LOGO 

            $pdf->Image('https://ww.nexxtsolutions.com/wp-content/uploads/2019/02/01-Cartimex-244x122.png', 7, 3, 40);

            // Establecer posición actual en la esquina superior derecha
            $pdf->SetXY(120, 10);
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(77, 9, '  ROL DE PAGO NO: ' . $row['ID'], 0, 0, 'R');
            $pdf->SetX($pdf->GetX() + 20);
            $pdf->Ln(); // Salto de línea
            ////// 
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(120, 6, "Concepto", 'L,T', 0, 'L');
            $pdf->Cell(25, 6, "ID", 'T', 0, 'C');
            $pdf->Cell(43, 6, "Fecha", 'T,R', 0, 'C');
            $pdf->SetFont('Arial', '', 8);
            $pdf->Ln(); // Salto de línea
            $pdf->Cell(120, 5, $row["Concepto"], 'L,B', 0, 'L');
            $pdf->Cell(25, 5, $row["ID"], 'B', 0, 'C');
            $pdf->Cell(43, 5, date('d/m/Y', strtotime($row["Fecha"])), 'B,R', 0, 'C');
        }

        // Celda de Cuerpo 

        $pdf->Ln(8);
        $pdf->Cell(19, 5, "Clase", 'B,T,L', 0, 'C',);
        $pdf->Cell(19, 5, "Tipo", 'B,T', 0, 'C');
        $pdf->Cell(19, 5, "Ref", 'B,T', 0, 'C');
        $pdf->Cell(90, 5, "Detalle", 'B,T', 0, 'C');
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->Cell(20, 5, "Ingreso", 'B,T', 0, 'R');
        $pdf->Cell(21, 5, "Egreso", 'B,T,R', 0, 'R');
        $pdf->SetFont('Arial', '', 8);
        $pdf->Ln();
        $pdf->Ln(2);


        for ($i = 0; $i < count($cuerpo); $i++) {

            $pdf->SetDrawColor(255, 255, 255);

            $pdf->Cell(19, 5, $cuerpo[$i]["Clase"], 1, 0, 'C');
            $pdf->Cell(19, 5, $cuerpo[$i]["Tipo"], 1, 0, 'C');
            $pdf->Cell(19, 5, $cuerpo[$i]["Referencia"], 1, 0, 'L');
            $detalle = $cuerpo[$i]["Detalle"];
            $ingreso = "$" . number_format($cuerpo[$i]["Ingreso"], 2, '.', '');
            $egreso = "$" . number_format($cuerpo[$i]["Egreso"], 2, '.', '');

            if (strlen($detalle) > 50) {

                $lineas = explode("\n", wordwrap($detalle, 50, "\n"));
                $detalle = $lineas[0];

                $pdf->SetFont('Arial', '', 8);
                $pdf->Cell(90, 5, $detalle, 1, 0, 'L');
                $pdf->SetFont('Arial', '', 8);
                $pdf->Cell(20, 5, $ingreso, 1, 0, 'R');
                $pdf->Cell(19, 5, $egreso, 1, 0, 'R');
                $pdf->Ln();

                for ($j = 1; $j < count($lineas); $j++) {
                    $detalle = $lineas[$j];
                    $pdf->Cell(57, 2, '', 1, 0, 'L');
                    $pdf->SetFont('Arial', '', 8);
                    $pdf->Cell(57, 2, $detalle, 1, 0, 'L');
                    $pdf->SetFont('Arial', '', 8);
                    $pdf->Ln();
                }
            } else {

                $pdf->SetFont('Arial', '', 8);
                $pdf->Cell(90, 5, $detalle, 1, 0, 'L');
                $pdf->Cell(20, 5, $ingreso, 1, 0, 'R');
                $pdf->Cell(19, 5, $egreso, 1, 0, 'R');
                $pdf->Ln();
            }
        }

        // TOTAL  

        $pdf->Ln(4);
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(150, 7, "", 'T,R', '', 'C');
        $pdf->Cell(20, 7, '$' . number_format($row["Ingreso_Total"], 2), 1, 0, 'R');
        $pdf->Cell(20, 7, '$' . number_format($row["Egreso_Total"], 2), 1, 0, 'R');
        $pdf->SetFont('Arial', '', 8);
        $pdf->Ln();

        $pdf->Ln();
        $pdf->Ln();
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(0, 8, "Valor Neto a Recibir:     $" . number_format($row["Valor_Neto"], 2, ".", ","), 0, 0, 'C');
        $pdf->Ln();
        $pdf->SetFont('Arial', '', 8);


        date_default_timezone_set('America/Guayaquil');
        $dateString = date('Ymd');
        $filename = 'fpdf/' . $ID_rol . $dateString . '.pdf';
        $pdf->Output($filename, 'F');

    } catch (PDOException $e) {

        $e = $e->getMessage();
        echo ($e);
    }
}


function Cargar_Roles()
{

    require_once('conexion.php');


    $fecha_inicio = date('Ym01', strtotime('last month'));
    $fecha_fin = date('Ymt', strtotime('last month'));

    $pdo = new PDO("sqlsrv:server=$sql_serverName;Database=$sql_database", $sql_user, $sql_pwd);

    $result = $pdo->prepare("SELECT TOP 5  ID   FROM EMP_ROLES WHERE Fecha BETWEEN :fecha_inicio AND :fecha_fin");

    $result->bindParam(':fecha_inicio', $fecha_inicio);
    $result->bindParam(':fecha_fin', $fecha_fin);

    if ($result->execute()) {
        $datos = $result->fetchAll(PDO::FETCH_ASSOC);
        var_dump($datos);

        for ($i = 0; $i < 5; $i++) {

            Crear_PDF($datos[0]["ID"]);

            echo $i;

            // echo $datos[$i]["ID"];

        }
        
    } else {
        $err = $result->errorInfo();
        var_dump($err);
    }
}
Cargar_Roles();
