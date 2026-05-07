**Manual Técnico de Arquitectura de Datos: Proyecto G.I.A.**

**Versión:** 1.0

**Estatus:** Fase de Diseño de Base de Datos Finalizada

**Plataforma:** XAMPP (MariaDB/MySQL)

---

**1\. Introducción**

El **Gestor de Inventario ASTI (G.I.A.)** está fundamentado en un modelo relacional normalizado (3FN) para garantizar que la información de los activos informáticos, redes y consumibles sea íntegra, trazable y fácil de consultar mediante búsquedas predictivas.

**2\. Glosario de Términos Técnicos**

Para asegurar una comunicación clara entre los administradores del sistema, definimos los siguientes conceptos clave:

* **ASTI:** Auxiliar de Soporte Técnico en Informática. Usuario con privilegios de gestión en el sistema.  
* **Atomicidad:** Propiedad que asegura que cada campo de la base de datos contenga un solo dato (ej. no mezclar nombre y apellidos en un campo de serie).  
* **FK (Foreign Key):** Llave foránea. Campo que crea un vínculo físico entre dos tablas (ej. id\_unidad en la tabla de equipos).  
* **Índice (Index):** Estructura de datos que mejora la velocidad de las operaciones de búsqueda en una tabla a costa de un poco de espacio en disco.  
* **Integridad Referencial:** Regla que evita que se elimine una Unidad si todavía tiene equipos asignados a ella.  
* **Offline-First:** Estrategia de desarrollo donde la aplicación móvil funciona sin red y sincroniza los datos cuando detecta conexión.  
* **PK (Primary Key):** Llave primaria. Identificador único e irrepetible de cada registro en una tabla.  
* **PROYECTO:** Programa Nacional de Activación de Equipos de Cómputo (Identificador institucional específico).  
* **UK (Unique Key):** Llave única. Asegura que no existan dos registros con el mismo valor (ej. dos laptops con la misma serie).

---

**3\. Especificaciones de Entidades**

El sistema se divide en tres capas de datos:

**A. Capa de Control (Catálogos)**

* **Unidades:** Define la ubicación física y zona geográfica.  
* **Empleados:** Gestiona tanto a los usuarios responsables de los equipos como a los administradores del sistema.

**B. Capa de Activos (Inventario)**

* **Equipos de Cómputo / Impresoras / TV / Telefonía:** Tablas especializadas con campos técnicos específicos para cada hardware. Todas comparten los campos de serie, id\_unidad y estatus.

**C. Capa de Infraestructura**

* **Redes:** Gestión de la columna vertebral del soporte (Switches y Routers).  
* **Consumibles:** Control de stock de materiales de soporte rápido.

---

**4\. Matriz de Relaciones y Trazabilidad**

| Tabla Origen | Campo Relacional | Tabla Destino | Función |
| :---- | :---- | :---- | :---- |
| unidades | id | empleados | Ubica al ASTI en su centro de trabajo. |
| unidades | id | Todas las de Activos | Segmenta el inventario por unidad médica o administrativa. |
| empleados | id | equipos\_computo | Asigna responsabilidad legal del bien informático. |
| empleados | matricula | modificado\_por | Registra quién realizó el último cambio (Auditoría). |


---

**Nota para el desarrollador:** Para mantener el rendimiento, se recomienda no eliminar los índices creados en serie e ip, ya que el buscador predictivo depende directamente de ellos para mantener una latencia menor a **200ms**.


**Documentación Maestra de Diseño de Interfaz (UI/UX) \- Proyecto G.I.A.**

**1\. Sistema de Diseño (Identidad Visual)**

* **Filosofía:** "Menos es Más" (Minimalista y Funcional).  
* **Modo:** Dark Mode Elegante.  
* **Paleta de Colores: Fondo \#121212, Superficies \#1E1E1E, Acento \#13322B (Verde IMSS). Fondo Base:** \#121212 (Negro). **Bordes y Separadores:** \#333333 (Gris Carbón).  
* **Comportamiento de Inputs:** Al hacer foco (focus) en cualquier campo de texto o búsqueda, el borde cambiará de gris a Verde IMSS con un sutil resplandor (glow).  
* **Navbar (Barra Superior):**  
  o    **Logo G.I.A.:** Ubicado a la izquierda. Al hacer clic, redirige siempre al Inicio (Buscador).  
  o    **Iconos de Categoría (Centro):** Solo visibles para usuarios logueados. Iconos limpios: Equipos de Cómputo (Icono: Laptop) Impresoras (Icono: Printer), Televisiones (Icono: Monitor), Telefonía (Icono: Phone), Infraestructura de Red (Icono: Server), Consumibles y Cables (Icono: BatteryFull)

  o    **Acceso (Derecha):** Un solo icono de `UserCircle` para Login/Registro.

---

**2\. Pantalla de Inicio (Dashboard)**

* **Buscador Central:** Barra tipo Google optimizada para búsqueda global (Serie, IP, Modelo, Unidad o Usuario).  
* **Resultados:** Lista predictiva con iconos que abre la **Ficha Técnica Total** directamente.

---

**3\. Estructura de Ventanas de Categoría (Tablas Dinámicas)**

Todas las ventanas se dividen en un **Layout de dos secciones**

**A. Barra Lateral Izquierda (Panel de Herramientas y Analítica)**

* **Buscador Local:** Mismo funcionamiento predictivo pero filtrado estrictamente por la categoría actual y la **Unidad asignada al ASTI logueado**.  
* **Estadísticas en Tiempo Real (Resumen por Categoría):**  
  * **Cómputo:** Total, conteo por Fabricante/Modelo, conteo por Tipo (AIO/CPU/Laptop), conteo por Área, conteo por Proyecto, conteo por Estatus.  
  * **Impresoras:** Total, conteo por Fabricante/Modelo, conteo por Tipo, conteo por Área.  
  * **TV:** Total, conteo por Fabricante/Modelo, conteo por Uso.  
  * **Telefonía:** Total, conteo por Fabricante/Modelo, conteo por Tipo.  
  * **Red:** Total por Tipo, Total por Área.  
  * **Consumibles:** Total por Tipo, Total por Categoría.

**B. Sección Derecha (Visualización de Datos)**

* **Menú de Acción Superior:** Iconos en Verde IMSS: \[+\] Agregar, \[↑\] Importar (con opción de descargar plantilla), \[↓\] Exportar.  
* **Tabla de Datos:** Listado dinámico con las columnas específicas.

 

**4\. Detalle de Columnas y Fichas Técnicas (Modales)**

**A. Equipos de Cómputo**

* **Columnas Tabla:** IP, TIPO, SERIE, USUARIO ASIGNADO, UNIDAD, AREA/DEPTO.  
* **Ficha Técnica (Modal):**  
  * **Hardware:** ID, Serie, Tipo, Fabricante, Modelo, Monitor, Proyecto.  
  * **Componentes:** Tipo Almacenamiento, Capacidad, RAM.  
  * **Red:** IP, Mac Net, Mac WiFi, Nodo, Puerto Router.  
  * **Ubicación:** Unidad, Área, Departamento, Extensión.  
  * **Trazabilidad Dinámica:**  
    * *Si Estatus \= ACTIVO:* Mostrar Fecha Instalación, Observaciones.  
    * *Si Estatus ≠ ACTIVO:* Mostrar Estatus, Fecha Retiro, Observaciones, Modificado Por (Matrícula).

**B. Impresoras**

* **Columnas Tabla:** SERIE, MODELO, UBICACIÓN, AREA/DEPTO, TIPO, IP.  
* **Ficha Técnica (Modal):**  
  * ID, Tipo, Serie, Fabricante, Modelo, Unidad, Área, Departamento, IP.  
  * **Trazabilidad Dinámica:** (Misma lógica: Activo vs No Activo).

**C. Televisiones**

* **Columnas Tabla:** SERIE, FABRICANTE, MODELO, AREA/DEPTO.  
* **Ficha Técnica (Modal):**  
  * ID, Serie, Fabricante, Modelo, Unidad, Área, Departamento, Uso.  
  * **Trazabilidad Dinámica:** (Misma lógica: Activo vs No Activo).

**D. Telefonía**

* **Columnas Tabla:** AREA/DEPTO, EXTENSIÓN.  
* **Ficha Técnica (Modal):**  
  * ID, Serie, Fabricante, Modelo, Tipo (Analógico/IP), IP, Nombre, Extensión, Nodo, Puerto Router, Unidad, Área, Departamento.  
  * **Trazabilidad Dinámica:** (Misma lógica: Activo vs No Activo).

**E. Infraestructura de Red**

* **Columnas Tabla:** SERIE, TIPO, FABRICANTE, MODELO, AREA.  
* **Ficha Técnica (Modal):**  
  * ID, Tipo, Serie, Fabricante, Modelo, Num Puertos, IP Gestión, MAC, Nodo Uplink, Unidad, Área, Estatus, Observaciones, Modificado Por.

**F. Consumibles y Cables**

* **Columnas Tabla:** TIPO, CATEGORÍA, CANTIDAD STOCK, UNIDAD, ESTATUS.  
* **Ficha Técnica (Modal):**  
  * ID, Tipo, Categoría, Longitud, Cantidad Stock, Unidad, Estatus (Nuevo/Usado), Observaciones, Modificado Por.

---

**4\. Ventanas de Configuración**

**A. Gestión de Unidades**

* **Vista:** Tabla con Columnas (ID, Clave, Unidad, Zona).  
* **Acción:** Botón para agregar nueva unidad (Clave, Nombre, Zona \[Selector\]).

**B. Gestión de Usuarios (Empleados)**

* **Vista:** Tabla con Columnas (ID, Matrícula, Nombre, Usuario, Categoría, Unidad).  
* **Acción:** Registro de nuevo usuario asignándole una unidad de las creadas anteriormente.

---

**5\. El Corazón del Sistema: El Buscador Predictivo (Vista Inicio)**

* **Diseño:** Solo el logotipo de G.I.A. y una barra de búsqueda de 600px de ancho centrada vertical y horizontalmente.  
* **Lógica de Navegación:**  
  1. El usuario escribe (ej: "172.27").  
  2. Se despliega un listado sombreado bajo la barra con:  
     * \[Icono Laptop\] 172.27.201.10 \- HP ProDesk \- Serie: MXL123...  
     * \[Icono Printer\] 172.27.201.50 \- LaserJet M404 \- Serie: PHB456...  
  3. Al dar clic en un resultado, se abre directamente el **Modal de Ficha Técnica Total** correspondiente.

---

**6\. Protocolo de Seguridad Invisible (UX de Validación)**

1. **Estado Inicial:** Todos los campos en los modales son etiquetas de texto (no editables).  
2. **Activación:** El usuario da clic en el icono de "Lápiz" (Editar).  
3. **Desbloqueo:** El modal se refresca y las etiquetas pasan a ser Inputs. El botón "Guardar" aparece en Verde IMSS en la esquina inferior derecha.

