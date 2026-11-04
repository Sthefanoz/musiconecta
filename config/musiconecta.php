<?php

return [
    // Comisión de la plataforma sobre el monto total de cada contratación (c = 5 %).
    'comision' => 0.05,

    // Porcentaje del monto total que el cliente paga como anticipo al ser aceptada.
    'anticipo' => 0.30,

    // Métodos de pago simulados.
    'metodos_pago' => ['tarjeta' => 'Tarjeta', 'transferencia' => 'Transferencia', 'efectivo' => 'Efectivo'],
];
