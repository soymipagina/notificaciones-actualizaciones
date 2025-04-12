#!/bin/bash

# Ruta del plugin
PLUGIN_DIR="$HOME/Google Drive/Unidades compartidas/Soymipagina/Plugins/notificaciones-actualizaciones"

# Nombre del zip que se va a generar
ZIP_NAME="notificaciones-actualizaciones.zip"

# Directorio donde se guarda el ZIP (el mismo del plugin)
OUTPUT_DIR="$PLUGIN_DIR"

# Navegar al directorio del plugin
cd "$PLUGIN_DIR" || { echo "❌ No se encontró el directorio del plugin"; exit 1; }

# Eliminar ZIP anterior si existe
rm -f "$OUTPUT_DIR/$ZIP_NAME"

# Crear el nuevo ZIP excluyendo este script y archivos basura
zip -r "$OUTPUT_DIR/$ZIP_NAME" . -x "*.DS_Store" "generar-zip.sh"

echo "✅ ¡ZIP generado exitosamente en: $OUTPUT_DIR/$ZIP_NAME"
