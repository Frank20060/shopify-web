<?php
/**
 * SEEDER - ARCHIVO DE CARGA DE DATOS
 * Este archivo se encarga de "limpiar" la base de datos y cargar los ejercicios predefinidos.
 */

require 'db.php';

echo "--- INICIANDO PROCESO DE CARGA DE DATOS ---\n";

// 2. LIMPIEZA TOTAL (NUKE)
R::nuke(); 
echo "Base de datos borrada (Nuke realizado).\n";

// 3. CREAR USUARIO ADMIN
$user = R::dispense('user');
$user->username = 'superuser';
$user->password = password_hash('super1user2', PASSWORD_DEFAULT);
$user->role = 'admin';
R::store($user);
echo "Usuario administrador 'superuser' creado.\n";

// 3.5. CREAR CATEGORÍAS POR DEFECTO
$default_categories = [
    ['name' => 'Liquid Mastery', 'slug' => 'liquid', 'description' => 'Aprende el lenguaje de plantillas de Shopify', 'color' => '#008060'],
    ['name' => 'Shopify Functions', 'slug' => 'functions', 'description' => 'Domina el backend con Rust y WebAssembly', 'color' => '#5a31f4']
];

foreach ($default_categories as $cat_data) {
    $category = R::dispense('category');
    $category->name = $cat_data['name'];
    $category->slug = $cat_data['slug'];
    $category->description = $cat_data['description'];
    $category->color = $cat_data['color'];
    R::store($category);
}
echo "Categorías por defecto creadas.\n";

// 4. DEFINICIÓN DE EJERCICIOS (ELITE 20)
$exercises = [
    // --- SECCIÓN LIQUID ---
    ['Liquid 1: Output y Filtros', 'l-1', 'Liquid', 'Básico', 'Accede al objeto `product`. Muestra el título en mayúsculas, seguido de un espacio, un guion, otro espacio, y el precio con formato `money`. Todo en una única línea.', '{{ product.title | upcase }} - {{ product.price | money }}', 'ZAPATILLAS - 50,00 €'],
    ['Liquid 2: Lógica de Stock', 'l-2', 'Liquid', 'Básico', 'Si el producto está disponible (`available`), imprime "DISPONIBLE". Si no, imprime "AGOTADO". Sin espacios extra ni saltos de línea.', "{% if product.available %}DISPONIBLE{% else %}AGOTADO{% endif %}", 'DISPONIBLE'],
    ['Liquid 3: Asignación e IVA', 'l-3', 'Liquid', 'Básico', 'Crea una variable `iva` que sea el precio del producto por 0.21. Luego imprime la frase "IVA: " seguido del valor con formato `money`.', "{% assign iva = product.price | times: 0.21 %}IVA: {{ iva | money }}", 'IVA: 10,50 €'],
    ['Liquid 4: Lista de Tags', 'l-4', 'Liquid', 'Intermedio', 'Genera una lista `<ul>`. Dentro, recorre los `product.tags` y pon cada uno en un `<li>`. Asegura el cierre de todas las etiquetas HTML.', "<ul>{% for tag in product.tags %}<li>{{ tag }}</li>{% endfor %}</ul>", '<ul><li>Nuevo</li><li>Oferta</li></ul>'],
    ['Liquid 5: Primera Palabra', 'l-5', 'Liquid', 'Intermedio', 'Muestra solo la primera palabra del título del producto usando `split` por espacio y el filtro `first`.', "{{ product.title | split: ' ' | first }}", 'Camiseta'],
    ['Liquid 6: Fecha Formateada', 'l-6', 'Liquid', 'Intermedio', 'Muestra la fecha actual (`now`) con el formato exacto: Año-Mes-Día (ej: 2024-05-20).', "{{ 'now' | date: '%Y-%m-%d' }}", '2026-05-08'],
    ['Liquid 7: Unless VIP', 'l-7', 'Liquid', 'Intermedio', 'Usa `unless` para imprimir "ACCESO DENEGADO" si el cliente NO tiene el tag "VIP".', "{% unless customer.tags contains 'VIP' %}ACCESO DENEGADO{% endunless %}", 'ACCESO DENEGADO'],
    ['Liquid 8: Capture Saludo', 'l-8', 'Liquid', 'Avanzado', 'Usa `capture` para guardar la cadena "CLIENTE: " seguida del nombre del cliente. Luego imprímela.', "{% capture info %}CLIENTE: {{ customer.first_name }}{% endcapture %}{{ info }}", 'CLIENTE: Juan'],
    ['Liquid 9: Handle Colección', 'l-9', 'Liquid', 'Avanzado', 'Busca en `collections` la que tiene handle "verano" e imprime el título de su primer producto.', "{{ collections['verano'].products.first.title }}", 'Gafas de Sol'],
    ['Liquid 10: Pluralización', 'l-10', 'Liquid', 'Avanzado', 'Imprime el número de items del carrito, un espacio, y la palabra "artículo" pluralizada.', "{{ cart.item_count }} {{ cart.item_count | pluralize: 'artículo', 'artículos' }}", '3 artículos'],

    // --- SECCIÓN FUNCTIONS ---
    ['Functions 1: GraphQL Input', 'f-1', 'Functions', 'Básico', 'Query GraphQL para obtener el `id` de las líneas del carrito y el `title` de sus productos.', "query Input { cart { lines { merchandise { ... on ProductVariant { id product { title } } } } } }", '{ "cart": { "lines": [...] } }'],
    ['Functions 2: Descuento Fijo', 'f-2', 'Functions', 'Básico', 'JSON de salida para un descuento de 10 unidades monetarias.', "{\"discounts\":[{\"value\":{\"fixedAmount\":10}}]}", '{"discounts": [{"value": {"fixedAmount": 10}}]}'],
    ['Functions 3: Rust STDIN', 'f-3', 'Functions', 'Básico', 'Define `fn main` que lea la entrada JSON de `io::stdin()`.', "fn main() -> Result<(), Box<dyn Error>> { let input: Input = serde_json::from_reader(io::stdin())?; Ok(()) }", 'Standard Rust Main'],
    ['Functions 4: Metafield Value', 'f-4', 'Functions', 'Intermedio', 'Ruta completa para acceder al valor de un metafield en el input de la función.', "input.extension.metafield.value", 'valor_config'],
    ['Functions 5: Validación Rust', 'f-5', 'Functions', 'Intermedio', 'Condición en Rust para devolver un error si la cantidad es superior a 5.', "if line.quantity > 5 { return Err(\"Error\"); }", 'Result::Err'],
    ['Functions 6: Hide Payment', 'f-6', 'Functions', 'Intermedio', 'Operación JSON para ocultar el método de pago con ID "cod".', "{\"operations\":[{\"hide\":{\"paymentMethodId\":\"cod\"}}]}", '{"operations": [{"hide": {"paymentMethodId": "cod"}}]}'],
    ['Functions 7: Weight Check', 'f-7', 'Functions', 'Avanzado', 'Si el peso total es mayor a 100, llama a la función `hide_shipping`.', "if total_weight > 100.0 { hide_shipping(); }", 'Lógica condicional'],
    ['Functions 8: VIP Check', 'f-8', 'Functions', 'Avanzado', 'Verifica si el cliente tiene el atributo `vip` activo.', "if customer.vip == true { }", 'Boolean check'],
    ['Functions 9: Line Attribute', 'f-9', 'Functions', 'Avanzado', 'Añade un atributo de línea con clave "custom" y valor "yes".', "{\"attribute\":{\"key\":\"custom\",\"value\":\"yes\"}}", '{"attribute": {"key": "custom", "value": "yes"}}'],
    ['Functions 10: Null Output', 'f-10', 'Functions', 'Avanzado', 'Estructura JSON mínima de salida sin descuentos ni operaciones.', "{\"discounts\":[],\"operations\":[]}", '{"discounts":[], "operations":[]}']
];

// 5. BUCLE DE INSERCIÓN
foreach ($exercises as $ex) {
    $m = R::dispense('module');
    $m->title = $ex[0];
    $m->slug = $ex[1];
    $m->category = $ex[2];
    $m->difficulty = $ex[3];
    $m->instruction = $ex[4];
    $m->solution = $ex[5];
    $m->expected_output = $ex[6];
    $m->user_progress = ""; 
    $m->completed = false; 
    R::store($m);
}

echo "--- CARGA FINALIZADA CON ÉXITO: 20 EJERCICIOS Y ADMIN CREADOS ---\n";
