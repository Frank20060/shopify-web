# 🚀 Guía de Maestría: Shopify Liquid

Bienvenido a tu plataforma de estudio. Este es un archivo de prueba para verificar que el renderizado de **Markdown** y el resaltado de sintaxis funcionan correctamente.

## ¿Qué es Liquid?
Liquid es un lenguaje de plantillas de código abierto escrito en Ruby y creado por Shopify. Es la columna vertebral de todos los temas de Shopify.

### 1. Etiquetas de Salida (Output)
Se usan para mostrar datos de los objetos de Shopify.

```liquid
{{ product.title | upcase }}
{{ customer.first_name | default: "Amigo" }}
```

### 2. Etiquetas de Lógica (Tags)
Se usan para crear la lógica del tema.

> [!TIP]
> Usa siempre `assign` para crear variables temporales y limpiar tu código.

```liquid
{% assign es_vip = false %}
{% if customer.tags contains 'VIP' %}
  {% assign es_vip = true %}
{% endif %}

{% if es_vip %}
  <p>¡Bienvenido, cliente de elite!</p>
{% else %}
  <p>Únete a nuestro club para ofertas exclusivas.</p>
{% endif %}
```

## 🛠️ Shopify Functions (Próximamente)
Las funciones te permiten extender la lógica del backend de Shopify usando **Rust** y WebAssembly.

- **Descuentos personalizados**
- **Validación de carrito**
- **Lógica de bundles**

---
*Este documento ha sido generado para probar el sistema de documentación de ShopifyMastery.*
