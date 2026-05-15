# 📘 Liquid — El lenguaje de plantillas

Guía definitiva de aprendizaje para desarrolladores de temas Shopify.

## ¿Qué es Liquid y para qué sirve?

Liquid es el lenguaje de plantillas creado por Shopify que permite construir páginas web dinámicas. Mezcla HTML estático con lógica dinámica en el mismo archivo. Su único trabajo es generar texto (normalmente HTML) a partir de datos que Shopify le proporciona.

```
DATOS SHOPIFY → SERVIDOR .LIQUID → LÓGICA & SUSTITUCIÓN → HTML PURO AL CLIENTE
```

No es un lenguaje de programación completo: no puede hacer peticiones de red ni tiene acceso al sistema de archivos del servidor.

## Sintaxis fundamental

Liquid tiene exactamente tres tipos de construcciones. Aprenderlas bien es lo primero:

### Objetos {{ }}
Muestran el valor de una variable. Lo que hay dentro se **evalúa y se imprime**.

```liquid
{{ product.title }}
{{ product.price | money }}
```

### Etiquetas {% %}
Ejecutan lógica pero **no imprimen nada** por sí solas. Aquí van condicionales, bucles, etc.

```liquid
{% if product.available %}
{% for tag in product.tags %}
{% assign descuento = 10 %}
```

### Comentarios
Contenido ignorado completamente por el motor de Liquid.

```liquid
{% comment %}
  Este texto no aparecerá en el HTML
{% endcomment %}
```

## Los objetos globales de Shopify

Cuando una plantilla .liquid se ejecuta, Shopify inyecta automáticamente una serie de objetos con datos de la tienda:

| Objeto | Disponible en | Contiene |
|--------|---------------|----------|
| `product` | Página de producto | Todo el producto actual |
| `collection` | Página de colección | La colección y sus productos |
| `cart` | Carrito | Líneas, totales, ítems |
| `customer` | Siempre (si está logueado) | Datos del cliente |
| `shop` | Siempre | Nombre, moneda, dominio |
| `request` | Siempre | URL actual, parámetros |
| `settings` | Siempre | Configuración del tema |

**El más importante para el trabajo diario es `product`.**

## El objeto product en detalle

Cuando estás en una página de producto, Shopify te proporciona automáticamente el objeto `product` con toda la información del producto actual:

```liquid
{{ product.title }}             → "Camiseta Azul Marino"
{{ product.handle }}            → "camiseta-azul-marino"
{{ product.description }}       → HTML con la descripción
{{ product.vendor }}            → "Nike"
{{ product.type }}              → "Ropa"
{{ product.available }}         → true o false
{{ product.price }}             → 2999  (CÉNTIMOS)
{{ product.compare_at_price }}  → 3999  (precio original)
{{ product.price_min }}         → precio mínimo entre variantes
{{ product.price_max }}         → precio máximo entre variantes
{{ product.tags }}              → ["verano", "oferta", "nuevo"]
{{ product.variants }}          → array de variantes
{{ product.images }}            → array de imágenes
{{ product.featured_image }}    → imagen principal
{{ product.metafields }}        → campos personalizados
```

> **⚠️ LOS PRECIOS SIEMPRE ESTÁN EN CÉNTIMOS.** El `2999` es `29,99 €`. Esto es intencional para evitar errores de punto flotante en cálculos monetarios.

## 💰 El filtro money y los precios

### ¿Por qué existe?
Como los precios están en céntimos, necesitas una forma de mostrarlos correctamente formateados con el síbolo de moneda, los separadores decimales de cada país y el código de divisa si la tienda vende en varias monedas. El filtro `money` hace todo eso automáticamente.

```liquid
{{ product.price | money }}
→ €29,99   (si la tienda está en euros)
```

### Variantes del filtro money

| Filtro | Salida para 2999 | Cuándo usarlo |
|--------|------------------|---------------|
| `money` | €29,99 | Uso general, el más común |
| `money_with_currency` | €29,99 EUR | Tiendas multi-moneda |
| `money_without_trailing_zeros` | €30 | Precios redondos |
| `money_without_currency` | 29,99 | Cuando ya muestras el síbolo por separado |

### Operar con precios antes de formatear
Hay situaciones donde necesitas hacer cálculos con el precio antes de mostrarlo. Puedes aplicar filtros matemáticos primero y `money` al final:

```liquid
{{ product.price | times: 2 | money }}
→ Multiplica por 2 y luego lo formatea

{{ product.compare_at_price | minus: product.price | money }}
→ Muestra cuánto ahorras (precio original - actual)
```

## 🔄 Control de flujo: if, elsif, else, unless

### La estructura básica
```liquid
{% if condición %}
  <!-- se ejecuta si la condición es verdadera -->
{% elsif otra_condición %}
  <!-- se ejecuta si la primera fue falsa y esta es verdadera -->
{% else %}
  <!-- se ejecuta si ninguna condición fue verdadera -->
{% endif %}
```

**Importante:** Toda construcción `if` debe cerrarse con `{% endif %}`.

### Operadores de comparación
```
==    igual a
!=    distinto de
>     mayor que
<     menor que
>=    mayor o igual que
<=    menor o igual que
contains   contiene (funciona con strings y arrays)
```

### Operadores lógicos
```liquid
{% if condicion_a and condicion_b %}   → ambas deben ser verdaderas
{% if condicion_a or condicion_b %}    → basta con que una sea verdadera
```

### unless — la negación semántica
`unless` ejecuta su bloque cuando la condición es **falsa**. Es lo opuesto de `if`.

```liquid
{% unless product.available %}
  <p>Este producto está agotado.</p>
{% endunless %}
```

### Valores falsos en Liquid
En Liquid, solo dos valores son falsos: `false` y `nil` (nulo). Todo lo demás, incluido `0` y `""`, es verdadero. Esto es diferente de JavaScript.

```liquid
{% if 0 %}     → verdadero (diferente a JS)
{% if "" %}    → verdadero
{% if nil %}   → falso
{% if false %} → falso
```

## 🔄 Bucle for y objeto forloop

### Sintaxis básica
```liquid
{% for elemento in colección %}
  {{ elemento }}
{% endfor %}
```

### El objeto forloop
Dentro de cualquier bucle `for`, Liquid inyecta automáticamente el objeto `forloop`:

```liquid
{% for tag in product.tags %}
  {{ forloop.index }}     → posición actual (empieza en 1)
  {{ forloop.index0 }}    → posición actual (empieza en 0)
  {{ forloop.rindex }}    → posición desde el final
  {{ forloop.first }}     → true si es el primer elemento
  {{ forloop.last }}      → true si es el último elemento
  {{ forloop.length }}    → total de elementos
{% endfor %}
```

### Modificadores del bucle
```liquid
{% for tag in product.tags limit: 3 %}
  → solo procesa los primeros 3

{% for tag in product.tags offset: 2 %}
  → salta los 2 primeros

{% for tag in product.tags limit: 3 offset: 2 %}
  → salta 2, luego procesa máximo 3
```

### El bloque else en un for
```liquid
{% for tag in product.tags %}
  <span>{{ tag }}</span>
{% else %}
  <p>Este producto no tiene etiquetas.</p>
{% endfor %}
```

### break y continue
```liquid
{% for variant in product.variants %}
  {% if variant.available == false %}
    {% break %}
  {% endif %}
  <option>{{ variant.title }}</option>
{% endfor %}
```

## 📝 Variables: assign y capture

### assign — guardar un valor simple
```liquid
{% assign nombre = valor %}
```

Crea una variable disponible para el resto de la plantilla:

```liquid
{% assign texto = "Hola" %}
{% assign numero = 42 %}
{% assign booleano = true %}
{% assign array = "a,b,c" | split: "," %}
```

### capture — guardar un bloque de HTML
`capture` funciona como `assign` pero para **bloques completos de contenido**, incluido HTML con lógica Liquid:

```liquid
{% capture nombre_variable %}
  <strong>{{ product.title }}</strong>
  {% if product.available %}disponible{% endif %}
{% endcapture %}

<!-- Más adelante, en la tarjeta del producto -->
{{ nombre_variable }}
```

### ¿Cuándo usar uno u otro?
- **Usa assign** para valores simples: números, strings cortos, booleanos.
- **Usa capture** para construir bloques de HTML que vas a reutilizar.

## 🧮 Filtros matemáticos

| Filtro | Operación | Ejemplo |
|--------|-----------|---------|
| `plus: n` | Suma | `{{ 5 \| plus: 3 }}` → 8 |
| `minus: n` | Resta | `{{ 10 \| minus: 4 }}` → 6 |
| `times: n` | Multiplica | `{{ 3 \| times: 4 }}` → 12 |
| `divided_by: n` | Divide | `{{ 10 \| divided_by: 2 }}` → 5 |
| `modulo: n` | Resto | `{{ 10 \| modulo: 3 }}` → 1 |
| `ceil` | Redondea arriba | `{{ 4.1 \| ceil }}` → 5 |
| `floor` | Redondea abajo | `{{ 4.9 \| floor }}` → 4 |
| `round` | Redondeo estándar | `{{ 4.5 \| round }}` → 5 |
| `abs` | Valor absoluto | `{{ -5 \| abs }}` → 5 |

### División entera vs. decimal
```liquid
{{ 10 | divided_by: 3 }}      → 3    (entera)
{{ 10 | divided_by: 3.0 }}    → 3.33 (decimal)
```

**Importante:** Para preservar decimales, asegúrate de que el divisor tenga `.0`.

## 📋 Filtros de arrays

| Filtro | Qué hace |
|--------|----------|
| `join: "sep"` | Une elementos en un string |
| `split: "sep"` | Convierte string en array |
| `first` | Devuelve el primer elemento |
| `last` | Devuelve el último elemento |
| `size` | Número de elementos |
| `reverse` | Invierte el orden |
| `sort` | Ordena alfabéticamente |
| `uniq` | Elimina duplicados |
| `map: "prop"` | Extrae propiedad de cada objeto |
| `where: "p", v` | Filtra elementos |

### Ejemplos prácticos
```liquid
<!-- join: convertir array en texto legible -->
{% assign etiquetas = product.tags | join: ", " %}
{{ etiquetas }}
→ "nuevo, oferta, verano"

<!-- map: extraer una propiedad -->
{% assign titulos = product.variants | map: "title" %}
{{ titulos | join: " / " }}
→ "S / M / L / XL"

<!-- where: filtrar por propiedad -->
{% assign disponibles = product.variants | where: "available", true %}
```

## 🧩 Patrones de lógica compleja

### Patrón: Detectar valor dentro de array
```liquid
{% assign encontrado = false %}
{% for tag in product.tags %}
  {% if tag == "oferta" %}
    {% assign encontrado = true %}
  {% endif %}
{% endfor %}

{% if encontrado %}
  <p>Este producto está en oferta!</p>
{% endif %}
```

### Patrón: HTML condicional con capture
```liquid
{% capture badge %}
  {% if product.available %}
    <span class="badge-success">En stock</span>
  {% else %}
    <span class="badge-danger">Agotado</span>
  {% endif %}
{% endcapture %}

<!-- Reutilizar en cualquier lugar -->
<div class="product-card">
  {{ badge }}
  <h2>{{ product.title }}</h2>
</div>
```

## 📝 Filtros de strings útiles

| Filtro | Ejemplo |
|--------|---------|
| `upcase` | `{{ "hola" \| upcase }}` → HOLA |
| `downcase` | `{{ "HOLA" \| downcase }}` → hola |
| `capitalize` | `{{ "hola mundo" \| capitalize }}` → Hola mundo |
| `truncate: n` | Recorta a n caracteres |
| `replace: "a", "b"` | Reemplaza a por b |
| `remove: "texto"` | Elimina todas las ocurrencias |
| `prepend: "texto"` | Añade al inicio |
| `append: "texto"` | Añade al final |
| `strip` | Elimina espacios al inicio/final |
| `handleize` | `{{ "Hola Mundo" \| handleize }}` → hola-mundo |

## 🏁 Checklist de maestría

- ✅ Formatear precios con filtros `money`.
- ✅ Controlar visibilidad con `if` y `unless`.
- ✅ Recorrer arrays con `for` y usar `forloop`.
- ✅ Realizar cálculos con filtros matemáticos.
- ✅ Capturar bloques de HTML para reutilización.
- ✅ Manipular arrays con `map` y `where`.
- ✅ Implementar flags booleanos para estados complejos.
- ✅ Usar operadores lógicos `and` y `or`.
- ✅ Transformar strings con filtros especializados.
- ✅ Construir patrones de lógica compleja combinando conceptos.