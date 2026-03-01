GUÍA DE COMENTARIOS Y SWAGGEREstándar de Documentación Automática | Versión: 1.0

🎯 1. ObjetivoGarantizar que toda la API esté documentada automáticamente mediante OpenAPI 3.0. Esto permite que el Frontend y la IA entiendan los datos sin consultar al desarrollador del Backend.

🛠️ 2. Estructura de Modelos (@OA\Schema)Cada modelo debe contener un bloque de comentario superior que describa todas sus propiedades.Reglas de Oro:Ejemplos Reales: Todo campo debe tener un example.Tipado Estricto: Definir type y, si aplica, format (int64, email, date-time).Nomenclatura: Las propiedades en el JSON deben ser idénticas a las de la base de datos (snake_case).Ejemplo de Propiedad Estándar:PHP * @OA\Property(
 * property="email",
 * type="string",
 * format="email",
 * description="Correo electrónico principal",
 * example="usuario@premiojoven.org"
 * )
 * 
📊 3. Tabla de Tipos y FormatosUsa esta referencia para mantener la consistencia en todo el proyecto:Dato en BDTipo OpenAPIFormatoEjemploID / FKintegerint64example=101UUIDstringuuidexample="550e8400-e29b..."Texto Largostring(ninguno)example="Notas del lead..."Fechasstringdate-timeexample="2026-03-01 10:00:00"Booleanosboolean(ninguno)example=trueDecimalesnumberfloatexample=1500.50

🔗 4. Relaciones y Objetos AnidadosCuando un modelo devuelve una relación (Eager Loading), se debe documentar como un object o un array.Objeto Simple (BelongsTo):PHP * @OA\Property(
 * property="vendedor",
 * type="object",
 * @OA\Property(property="id", type="integer", example=1),
 * @OA\Property(property="nombre", type="string", example="Juan")
 * )
Colección (HasMany):PHP * @OA\Property(
 * property="etiquetas",
 * type="array",
 * @OA\Items(ref="#/components/schemas/Etiqueta")
 * )

🛣️ 5. Documentación de EndpointsEn los controladores, cada método debe especificar su respuesta exitosa vinculándola al Schema del modelo.PHP/**
 * @OA\Get(
 * path="/api/clientes/{id}",
 * summary="Obtener un cliente específico",
 * tags={"Clientes"},
 * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 * @OA\Response(
 * response=200,
 * description="Operación exitosa",
 * @OA\JsonContent(ref="#/components/schemas/Cliente")
 * )
 * )
 */

🤖 6. Instrucción para la IA (Prompting)Cuando pidas a la IA generar código, añade siempre esta instrucción:"Genera el modelo y los comentarios de Swagger siguiendo estrictamente las reglas de docs/guia_comentarios.md e incluye ejemplos reales para cada propiedad."