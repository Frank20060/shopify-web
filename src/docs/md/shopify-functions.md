# ⚙️ Shopify Functions

Documentación completa para lógica de backend en el checkout.

## Índice Rápido
- [¿Qué son?](#qué-son-las-shopify-functions)
- [Tipos](#tipos-de-functions-disponibles)
- [Estructura](#estructura-de-un-proyecto-de-function)
- [GraphQL](#📡-graphql-pedir-datos-al-backend)
- [TypeScript](#🟦-typescript-en-functions-lo-básico)
- [Output Schema](#📤-el-esquema-de-output-qué-devuelve-una-function)
- [Dinero](#🔢-trabajar-con-importes-monetarios)
- [Ejemplos](#🏗️-ejemplos-de-lógica-completa)
- [Buenas Prácticas](#🛡️-buenas-prácticas-fundamentales)

## ¿Qué son las Shopify Functions?

Son fragmentos de código que se ejecutan **dentro de los servidores de Shopify** en momentos concretos del proceso de compra. Son la herramienta oficial para personalizar el comportamiento del checkout sin necesidad de un servidor externo propio.

### La diferencia clave con Liquid

| Característica | Liquid | Shopify Functions |
|---------------|---------|-------------------|
| **Dónde corre** | Servidor de Shopify al generar HTML | Runtime de Shopify durante el checkout |
| **Cuándo** | Al cargar una página | Durante una acción de compra |
| **Qué puede hacer** | Mostrar datos, HTML dinámico | Cambiar precios, pagos, envíos, validar |
| **Lenguaje** | Liquid | JavaScript / TypeScript / Rust |
| **Output** | HTML | JSON con instrucciones |

### El ciclo completo de una Function

El cliente hace algo en el checkout (añade producto, elige envío...) ↓ Shopify detecta que hay una Function registrada para ese evento ↓ Shopify ejecuta tu Function pasándole datos en JSON (el "input") ↓ Tu código lee esos datos y decide qué hacer ↓ Tu código devuelve instrucciones en JSON (el "output") ↓ Shopify aplica esas instrucciones al checkout

## Tipos de Functions disponibles

Cada tipo de Function interviene en un momento distinto del checkout y tiene un esquema de input/output diferente:

| Tipo | Para qué sirve |
|------|----------------|
| **Discount** | Aplicar descuentos porcentuales, fijos o de volumen |
| **Payment Customization** | Ocultar, reordenar o renombrar métodos de pago |
| **Delivery Customization** | Personalizar opciones de envío por país, total, etc. |
| **Cart Transform** | Expandir ítems del carrito o modificar su presentación |
| **Order Validation** | Bloquear el checkout si no se cumplen condiciones |
| **Fulfillment Constraints** | Reglas de cómo se puede cumplimentar un pedido |

## Estructura de un proyecto de Function

Todas las Functions siguen la misma estructura de archivos:

```
mi-function/
├── shopify.extension.toml   ← declara el tipo, nombre y API version
├── src/
│   ├── index.ts             ← lógica principal (tu código)
│   └── run.graphql          ← qué datos le pides a Shopify
└── package.json
```

### El archivo shopify.extension.toml
Define qué tipo de Function es esta extensión y qué API usa:

```toml
[[extensions]]
type = "function"
name = "Mi descuento personalizado"
handle = "mi-descuento"

[[extensions.targeting]]
module = "./src/index.ts"
target = "purchase.product-discount.run"
```

El campo `target` determina en qué momento del checkout se ejecuta tu código. Cada tipo de Function tiene su propio target.

## 📡 GraphQL: pedir datos al backend

GraphQL es un lenguaje de consulta para APIs. En lugar de recibir un objeto JSON fijo con todos los datos (como en REST), con GraphQL **defines exactamente qué datos quieres** y Shopify te devuelve solo esos.

### El archivo run.graphql
Cada Function tiene un archivo .graphql donde defines tu consulta. Shopify lo lee cuando despliega tu Function y se asegura de que los datos que pides estén disponibles cuando se ejecute.

```graphql
query RunInput {
  cart {
    lines {
      id
      quantity
    }
  }
}
```

Esto le dice a Shopify: "cuando ejecutes mi Function, dame el ID y la cantidad de cada línea del carrito".

### Anatomía completa de una query de Function
```graphql
query RunInput {
  cart {
    lines {
      id
      quantity
      merchandise {
        ... on ProductVariant {
          id
          title
          product {
            id
            title
            tags
          }
        }
      }
      cost {
        totalAmount {
          amount
        }
      }
    }
    cost {
      totalAmount {
        amount
      }
    }
    buyerIdentity {
      countryCode
    }
  }
}
```

### ¿Qué es ... on ProductVariant?
En GraphQL, merchandise puede ser de varios tipos (una variante de producto, una tarjeta regalo, etc.). El operador `... on TipoConcreto` se llama **fragment inline** y sirve para decir: "cuando el tipo sea ProductVariant, dame estos campos".

### Pedir metafields en la query
Los metafields son campos personalizados. Para pedirlos en la query usas la función metafield con su namespace y key:

```graphql
product {
  id
  descuentoVip: metafield(namespace: "custom", key: "descuento_vip") {
    value
  }
}
```

El alias antes de metafield es el nombre con el que accederás a ese dato en TypeScript: `producto.descuentoVip?.value`.

## 🟦 TypeScript en Functions: lo básico

Shopify Functions usan TypeScript (o JavaScript). Aquí solo necesitas conocer lo esencial.

### Tipos generados automáticamente
Cuando ejecutas `shopify app build`, Shopify genera automáticamente los tipos TypeScript a partir de tu run.graphql:

```typescript
import type { RunInput, FunctionRunResult } from "../generated/api";
```

- `RunInput` → tipo del objeto que recibe tu función (el input del carrito)
- `FunctionRunResult` → tipo que debe devolver tu función (las instrucciones)

### La función run
Tu Function siempre exporta una función llamada run:

```typescript
export function run(input: RunInput): FunctionRunResult {
  // input  → los datos del carrito que pediste en run.graphql
  // return → las instrucciones para Shopify
}
```

Shopify llama a esta función automáticamente. Tú no la llamas nunca directamente.

### Control de flujo en TypeScript
```typescript
// if / else if / else
const total = parseFloat(input.cart.cost.totalAmount.amount);
if (total >= 100) {
  // lógica para pedidos de 100€ o más
} else if (total >= 50) {
  // lógica para pedidos entre 50€ y 99€
}

// for...of — recorrer arrays
for (const linea of input.cart.lines) {
  const cantidad = linea.quantity;
}

// find — buscar un elemento
const metodoContraReembolso = input.paymentMethods.find(
  m => m.name.toLowerCase().includes("contra reembolso")
);

// reduce — acumular un valor
const totalUnidades = input.cart.lines.reduce(
  (suma, linea) => suma + linea.quantity,
  0
);

// filter — quedarse con parte de un array
const lineasCaras = input.cart.lines.filter(
  linea => parseFloat(linea.cost.totalAmount.amount) > 50
);
```

## 📤 El esquema de Output: qué devuelve una Function

El output varía según el tipo de Function. Aquí están los más importantes.

### Output de una Discount Function
```typescript
return {
  discounts: [
    {
      targets: [{ orderSubtotal: { excludedVariantIds: [] } }],
      value: { percentage: { value: "15" } },
      message: "15% de descuento"
    }
  ],
  discountApplicationStrategy: "FIRST"
}
```

#### Tipos de targets:
```typescript
// Al subtotal completo del pedido
targets: [{ orderSubtotal: { excludedVariantIds: [] } }]

// A una variante específica
targets: [{ productVariant: { id: "gid://shopify/ProductVariant/123" } }]
```

#### Tipos de value:
```typescript
// Descuento porcentual
value: { percentage: { value: "15" } }      // 15% de descuento

// Descuento de cantidad fija
value: { fixedAmount: { amount: "5.00" } }  // 5€ de descuento
```

#### discountApplicationStrategy:

| Valor | Comportamiento |
|-------|----------------|
| `"FIRST"` | Aplica solo el primer descuento de la lista |
| `"ALL"` | Aplica todos los descuentos acumulados |
| `"MAXIMUM"` | Aplica solo el descuento más grande |

### Output de una Payment Customization
```typescript
return {
  operations: [
    { hide: { paymentMethodId: "gid://shopify/PaymentMethod/123" } },
    { rename: { paymentMethodId: "...", newName: "Nuevo nombre" } }
  ]
}
```

### Output de una Order Validation
```typescript
// Sin errores → deja pasar
return { errors: [] }

// Con errores → bloquea el checkout
return {
  errors: [
    {
      localizedMessage: "El pedido debe ser mayor a 15€",
      target: "$.cart"
    }
  ]
}
```

## 🔢 Trabajar con importes monetarios

En las Functions, los importes no vienen en céntimos como en Liquid. Vienen como **strings decimales**: "29.99" (siempre con punto, sin símbolo de moneda).

### Conversión básica
```typescript
const totalStr = input.cart.cost.totalAmount.amount; // "29.99"
const total = parseFloat(totalStr);                   // 29.99

// Comparar con un límite
if (total > 500) {
  // el total supera 500€
}
```

### Trabajar en céntimos para evitar decimales
Si prefieres trabajar en céntimos para evitar problemas de punto flotante:

```typescript
const totalCentimos = Math.round(parseFloat(totalStr) * 100); // 2999

if (totalCentimos > 50000) {  // más de 500€
  // aplicar descuento
}
```

## 🏗️ Ejemplos de lógica completa

### Descuento por volumen de unidades
La lógica: contar unidades totales del carrito, aplicar un porcentaje escalonado.

```typescript
export function run(input: RunInput): FunctionRunResult {
  
  // 1. Contar el total de unidades
  const totalUnidades = input.cart.lines.reduce(
    (suma, linea) => suma + linea.quantity,
    0
  );
  
  // 2. Decidir el porcentaje
  let porcentaje = 0;
  if (totalUnidades >= 10) {
    porcentaje = 25;
  } else if (totalUnidades >= 6) {
    porcentaje = 15;
  } else if (totalUnidades >= 3) {
    porcentaje = 10;
  }
  
  // 3. Si no hay descuento, salir
  if (porcentaje === 0) {
    return { discounts: [], discountApplicationStrategy: "FIRST" };
  }
  
  // 4. Devolver el descuento
  return {
    discounts: [
      {
        targets: [{ orderSubtotal: { excludedVariantIds: [] } }],
        value: { percentage: { value: porcentaje.toString() } },
        message: `${porcentaje}% de descuento por volumen`
      }
    ],
    discountApplicationStrategy: "FIRST"
  };
}
```

### Ocultar un método de pago según el total
```typescript
export function run(input: RunInput): FunctionRunResult {
  
  const total = parseFloat(input.cart.cost.totalAmount.amount);
  
  if (total <= 500) {
    return { operations: [] }; // no hacer nada
  }
  
  // Buscar el método a ocultar
  const cod = input.paymentMethods.find(
    m => m.name.toLowerCase().includes("contra reembolso")
  );
  
  if (!cod) {
    return { operations: [] }; // el método no existe, no hacer nada
  }
  
  return {
    operations: [{ hide: { paymentMethodId: cod.id } }]
  };
}
```

### Lógica geográfica: countryCode
El campo cart.buyerIdentity.countryCode devuelve el código ISO-3166 del país del comprador ("ES", "FR", "DE", etc.).

```typescript
const pais = input.cart.buyerIdentity?.countryCode ?? "desconocido";

// Ejemplo: ocultar envío por correo a ciertos países
if (["ES", "PT"].includes(pais)) {
  // lógica para países ibéricos
}
```

### Validación del carrito
```typescript
export function run(input: RunInput): FunctionRunResult {
  
  const errores = [];
  const total = parseFloat(input.cart.cost.totalAmount.amount);
  
  // Validación: pedido mínimo de 15€
  if (total < 15) {
    const falta = (15 - total).toFixed(2);
    errores.push({
      localizedMessage: `Pedido mínimo 15€. Faltan ${falta}€.`,
      target: "$.cart"
    });
  }
  
  // Validación: no más de 10 unidades de un mismo producto
  for (const linea of input.cart.lines) {
    if (linea.quantity > 10) {
      errores.push({
        localizedMessage: "Máximo 10 unidades por producto",
        target: "$.cart.lines[0]"
      });
    }
  }
  
  return { errors: errores };
}
```

## 🛡️ Buenas Prácticas Fundamentales

- **✅ Siempre maneja el caso de ausencia de datos.** Los metafields pueden no existir, el cliente puede no estar logueado, countryCode puede ser nulo. Usa ?. y ?? defensivamente.
- **✅ Devuelve siempre un output válido.** Incluso cuando no hay nada que hacer, devuelve el esquema correcto con arrays vacíos.
- **✅ Los IDs de Shopify son GIDs.** Los IDs tienen el formato gid://shopify/ProductVariant/12345. Cuando los uses como target, pásalos tal cual.
- **✅ El campo amount es un string.** Siempre usa parseFloat() antes de operar con él.
- **✅ discountApplicationStrategy importa.** Si devuelves múltiples descuentos y usas "FIRST", solo se aplicará el primero. Usa "ALL" cuando quieras que se acumulen.

## 🏁 Lo que deberías poder hacer

Después de estudiar esta documentación, deberías ser capaz de:

- ✓ Escribir la query GraphQL correcta para pedir al carrito exactamente los datos necesarios.
- ✓ Construir una Function de descuento que aplique un porcentaje al subtotal.
- ✓ Implementar lógica de descuento por volumen con porcentajes escalonados.
- ✓ Ocultar métodos de pago según el total del carrito.
- ✓ Personalizar opciones de envío basándote en el countryCode.
- ✓ Bloquear el checkout con mensajes de error claros.
- ✓ Leer metafields y aplicar descuentos por línea.
- ✓ Construir correctamente el esquema de output de cualquier Function.
- ✓ Usar control de flujo avanzado: if/else, bucles, find, reduce, filter.
- ✓ Devolver múltiples descuentos simultáneos con la estrategia correcta.