from docx import Document
from docx.shared import Inches, Pt, RGBColor
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.enum.table import WD_TABLE_ALIGNMENT, WD_CELL_VERTICAL_ALIGNMENT
from docx.enum.section import WD_SECTION
from docx.oxml import OxmlElement
from docx.oxml.ns import qn
from docx.enum.style import WD_STYLE_TYPE
from pathlib import Path

OUT = Path(__file__).resolve().parents[1] / "docs" / "Guia_de_pruebas_Clinica_Vida_Salud.docx"

TEAL = "118D91"
NAVY = "16324F"
MINT = "DDF4F1"
BLUE = "2E6F9E"
GREEN = "16845B"
RED = "B94A48"
GRAY = "5F6B76"
LIGHT = "F3F7F9"

def shade(cell, fill):
    tc_pr = cell._tc.get_or_add_tcPr()
    shd = tc_pr.find(qn("w:shd"))
    if shd is None:
        shd = OxmlElement("w:shd")
        tc_pr.append(shd)
    shd.set(qn("w:fill"), fill)

def margins(cell, top=90, start=100, bottom=90, end=100):
    tc = cell._tc
    tcPr = tc.get_or_add_tcPr()
    tcMar = tcPr.first_child_found_in("w:tcMar")
    if tcMar is None:
        tcMar = OxmlElement("w:tcMar")
        tcPr.append(tcMar)
    for m, v in (("top", top), ("start", start), ("bottom", bottom), ("end", end)):
        node = tcMar.find(qn(f"w:{m}"))
        if node is None:
            node = OxmlElement(f"w:{m}")
            tcMar.append(node)
        node.set(qn("w:w"), str(v)); node.set(qn("w:type"), "dxa")

def set_cell_text(cell, text, bold=False, color=None, size=9):
    cell.text = ""
    p = cell.paragraphs[0]
    r = p.add_run(str(text))
    r.bold = bold
    r.font.name = "Aptos"
    r.font.size = Pt(size)
    if color: r.font.color.rgb = RGBColor.from_string(color)
    cell.vertical_alignment = WD_CELL_VERTICAL_ALIGNMENT.CENTER
    margins(cell)

def add_title(doc, text, subtitle=None):
    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p.paragraph_format.space_before = Pt(18)
    p.paragraph_format.space_after = Pt(8)
    r = p.add_run(text)
    r.bold = True; r.font.name = "Aptos Display"; r.font.size = Pt(25)
    r.font.color.rgb = RGBColor.from_string(NAVY)
    if subtitle:
        p2 = doc.add_paragraph()
        p2.alignment = WD_ALIGN_PARAGRAPH.CENTER
        r2 = p2.add_run(subtitle)
        r2.font.name = "Aptos"; r2.font.size = Pt(12); r2.font.color.rgb = RGBColor.from_string(TEAL)

def add_heading(doc, text, level=1):
    p = doc.add_heading(text, level=level)
    p.paragraph_format.keep_with_next = True
    return p

def add_bullets(doc, items, numbered=False):
    style = "List Number" if numbered else "List Bullet"
    for item in items:
        p = doc.add_paragraph(style=style)
        p.add_run(item)

def add_test(doc, number, title, role, steps, expected):
    p = doc.add_paragraph()
    p.paragraph_format.keep_with_next = True
    r = p.add_run(f"Prueba {number}. {title}")
    r.bold = True; r.font.size = Pt(12); r.font.color.rgb = RGBColor.from_string(NAVY)
    p2 = doc.add_paragraph()
    p2.paragraph_format.keep_with_next = True
    rr = p2.add_run(f"Rol recomendado: {role}")
    rr.bold = True; rr.font.color.rgb = RGBColor.from_string(TEAL)
    add_bullets(doc, steps, numbered=True)
    box = doc.add_table(rows=1, cols=1)
    box.alignment = WD_TABLE_ALIGNMENT.CENTER
    shade(box.cell(0,0), MINT)
    set_cell_text(box.cell(0,0), "Resultado esperado: " + expected, bold=False, color=NAVY, size=9)
    box.cell(0,0).paragraphs[0].runs[0].bold = True
    doc.add_paragraph().paragraph_format.space_after = Pt(0)

doc = Document()
sec = doc.sections[0]
sec.top_margin = Inches(0.65); sec.bottom_margin = Inches(0.65)
sec.left_margin = Inches(0.7); sec.right_margin = Inches(0.7)

styles = doc.styles
styles["Normal"].font.name = "Aptos"; styles["Normal"].font.size = Pt(10)
styles["Normal"].paragraph_format.space_after = Pt(5)
for name, size, color in (("Title", 25, NAVY), ("Heading 1", 17, NAVY), ("Heading 2", 13, TEAL), ("Heading 3", 11, BLUE)):
    st = styles[name]; st.font.name = "Aptos Display"; st.font.size = Pt(size); st.font.color.rgb = RGBColor.from_string(color); st.font.bold = True

add_title(doc, "Guía de pruebas del sistema", "Clínica Vida Salud · Examen 2 de Ingeniería de Requisitos")
p = doc.add_paragraph()
p.alignment = WD_ALIGN_PARAGRAPH.CENTER
r = p.add_run("Verificación realizada el 3 de noviembre de 2026")
r.italic = True; r.font.color.rgb = RGBColor.from_string(GRAY)

summary = doc.add_table(rows=2, cols=3)
summary.alignment = WD_TABLE_ALIGNMENT.CENTER
for i, (h, v) in enumerate((("Pruebas mínimas", "10/10 aprobadas"), ("Regresiones", "6/6 módulos aprobados"), ("Datos de prueba", "Revertidos automáticamente"))):
    shade(summary.cell(0,i), NAVY); set_cell_text(summary.cell(0,i), h, True, "FFFFFF", 9)
    shade(summary.cell(1,i), LIGHT); set_cell_text(summary.cell(1,i), v, True, GREEN, 10)

add_heading(doc, "1. Conclusión de la revisión", 1)
doc.add_paragraph("El proyecto cumple las diez pruebas mínimas solicitadas en el documento de la ingeniera. También se verificaron los módulos de citas, roles, paciente, médico, invitaciones y pagos. La comprobación automática se ejecutó dentro de una transacción que se revirtió al finalizar, por lo que no dejó pacientes ni citas de prueba en la base de datos.")
doc.add_paragraph("Durante la revisión se completaron dos puntos que estaban incompletos: la edición de médicos y los filtros de reportes por médico, especialidad y estado, además del contador de pacientes registrados.")

add_heading(doc, "2. Preparación antes de probar", 1)
add_bullets(doc, [
    "Inicia XAMPP y confirma que Apache esté en ejecución.",
    "Confirma que PostgreSQL esté iniciado.",
    "Abre http://localhost/clinica-vida-salud/public/ en el navegador.",
    "Usa datos de demostración. No uses información médica real durante la presentación.",
    "Para repetir pruebas de reservas, utiliza una fecha futura y un horario disponible.",
])

add_heading(doc, "3. Cuentas de demostración", 1)
table = doc.add_table(rows=1, cols=3)
table.alignment = WD_TABLE_ALIGNMENT.CENTER
for i, h in enumerate(("Rol", "Usuario", "Contraseña")):
    shade(table.cell(0,i), NAVY); set_cell_text(table.cell(0,i), h, True, "FFFFFF")
for role, user, pwd in [
    ("Paciente", "paciente.demo@vidasalud.local", "Paciente2026!"),
    ("Médico", "medico.demo@vidasalud.local", "Medico2026!"),
    ("Recepción", "recepcion.demo@vidasalud.local", "Recepcion2026!"),
    ("Administración", "admin", "Usar la contraseña administrativa actual"),
]:
    cells = table.add_row().cells
    for i, value in enumerate((role,user,pwd)):
        shade(cells[i], "FFFFFF" if len(table.rows)%2 else LIGHT); set_cell_text(cells[i], value, i==0, NAVY if i==0 else None)

add_heading(doc, "4. Matriz de cumplimiento", 1)
rows = [
    ("RF01", "Acceso, sesión, roles y cierre", "Cumple", "Acceso separado y bloqueo de páginas internas"),
    ("RF02", "Gestión de pacientes y CI único", "Cumple", "Alta, búsqueda, modificación, historial y estado"),
    ("RF03", "Gestión de médicos", "Cumple", "Alta, búsqueda, edición, agenda y estado"),
    ("RF04", "Especialidades", "Cumple", "Nombre único y normalizado"),
    ("RF05", "Horarios médicos", "Cumple", "Intervalos, duración y validación"),
    ("RF06", "Registro de citas", "Cumple", "Código único y disponibilidad"),
    ("RF07", "Evitar superposición", "Cumple", "Validación de servidor y PostgreSQL"),
    ("RF08", "Reprogramación", "Cumple", "Solo para estados permitidos"),
    ("RF09", "Cancelación", "Cumple", "Conserva historial y libera horario"),
    ("RF10", "Estados de cita", "Cumple", "Transiciones controladas"),
    ("RF11", "Agenda médica", "Cumple", "Filtros por médico, fecha y estado"),
    ("RF12", "Historial del paciente", "Cumple", "Fecha, médico, especialidad y estado"),
    ("RF13", "Reportes", "Cumple", "Rango, médico, especialidad, estado y pacientes"),
]
mt = doc.add_table(rows=1, cols=4)
mt.alignment = WD_TABLE_ALIGNMENT.CENTER
for i,h in enumerate(("Código", "Requisito", "Estado", "Evidencia")):
    shade(mt.cell(0,i), NAVY); set_cell_text(mt.cell(0,i), h, True, "FFFFFF", 8)
for code, req, state, evidence in rows:
    cells = mt.add_row().cells
    vals = (code, req, state, evidence)
    for i,v in enumerate(vals):
        shade(cells[i], LIGHT if len(mt.rows)%2==0 else "FFFFFF")
        set_cell_text(cells[i], v, i in (0,2), GREEN if i==2 else None, 8)

doc.add_page_break()
add_heading(doc, "5. Pruebas paso a paso para la presentación", 1)

add_test(doc, "1", "Registrar un paciente nuevo", "Recepción o administración", [
    "Inicia sesión y abre Pacientes.",
    "Pulsa Nuevo paciente.",
    "Completa nombres, apellidos, CI único, fecha de nacimiento, sexo y contacto.",
    "Pulsa Guardar paciente.",
    "Busca el CI recién registrado en la lista.",
], "El sistema muestra confirmación y el paciente aparece una sola vez en la lista.")

add_test(doc, "2", "Rechazar un CI duplicado", "Recepción o administración", [
    "Vuelve a abrir Nuevo paciente.",
    "Escribe el mismo CI usado en la prueba anterior y completa los campos obligatorios.",
    "Intenta guardar.",
], "El sistema rechaza el registro e informa que el documento ya existe.")

add_test(doc, "3", "Programar una cita disponible", "Paciente o recepción", [
    "Abre Solicitar cita o Nueva cita.",
    "Selecciona primero la especialidad y pulsa Continuar.",
    "Elige un médico; verifica que corresponda a la especialidad.",
    "Selecciona fecha futura y luego una hora marcada como disponible.",
    "Escribe un motivo de demostración, revisa el resumen y confirma.",
], "Se crea una cita con código único y estado Solicitada o Confirmada según el rol.")

add_test(doc, "4", "Impedir una cita superpuesta", "Recepción", [
    "Intenta reservar otra cita para el mismo médico, fecha y hora de la prueba 3.",
    "Completa el flujo hasta la validación final.",
], "El horario ocupado no se ofrece o el servidor rechaza la operación. Nunca se guardan dos citas activas superpuestas.")

add_test(doc, "5", "Rechazar una cita fuera del horario", "Recepción", [
    "Selecciona un médico y revisa sus horarios configurados.",
    "Intenta usar una fecha u hora que no pertenezca a su jornada; si la interfaz no la ofrece, esa ausencia ya demuestra la validación visual.",
], "El horario no aparece como seleccionable; una petición inválida también sería rechazada por el servidor y la base de datos.")

add_test(doc, "6", "Cancelar sin borrar el historial", "Paciente o recepción", [
    "Abre Mis citas o Citas y entra al detalle de una cita de demostración.",
    "Pulsa Cancelar, escribe un motivo y confirma.",
    "Vuelve al historial del paciente.",
], "La cita sigue visible con estado Cancelada, conserva el motivo y el horario queda libre para una nueva reserva.")

add_test(doc, "7", "Consultar historial del paciente", "Paciente, médico autorizado o recepción", [
    "Busca al paciente de demostración.",
    "Abre Historial o Ficha del paciente.",
    "Revisa las citas activas, atendidas y canceladas.",
], "Se muestran fecha, médico, especialidad, estado y datos de creación; no se muestran citas de otro paciente.")

add_test(doc, "8", "Consultar citas por médico y fecha", "Médico o recepción", [
    "Abre Mi agenda o Agenda.",
    "Selecciona una fecha que tenga citas.",
    "En recepción, filtra también por médico o estado si corresponde.",
], "La agenda muestra solamente las citas que coinciden con el médico y la fecha elegidos.")

add_test(doc, "9", "Bloquear una página interna sin sesión", "Sin sesión", [
    "Cierra sesión.",
    "Escribe directamente http://localhost/clinica-vida-salud/public/reportes.php en la barra del navegador.",
], "El sistema redirige al inicio de sesión y no permite ver información interna.")

add_test(doc, "10", "Rechazar una operación inválida", "Recepción o administración", [
    "Abre un formulario de paciente, médico, horario o cita.",
    "Deja un dato obligatorio vacío o introduce un formato inválido.",
    "Pulsa Guardar.",
], "La operación no se guarda y aparece un mensaje comprensible junto al campo o en la parte superior.")

add_heading(doc, "6. Pruebas complementarias por rol", 1)
add_heading(doc, "Paciente", 2)
add_bullets(doc, [
    "Verifica el panel con próximas citas, realizadas y canceladas.",
    "Comprueba el asistente de reserva por pasos: especialidad, médico, fecha, hora y confirmación.",
    "Abre Mis citas, detalle, comprobante PDF y perfil personal.",
    "Revisa Métodos de pago. PayPal y otros proveedores son opciones demostrativas; no procesan dinero real.",
])
add_heading(doc, "Médico", 2)
add_bullets(doc, [
    "Revisa agenda, próximas consultas y pacientes relacionados.",
    "Abre la ficha del paciente desde una cita asignada.",
    "Cambia el estado únicamente siguiendo el flujo permitido: confirmada, completada o no asistió.",
    "Comprueba Mi perfil y horarios y la lista de notificaciones.",
])
add_heading(doc, "Recepción", 2)
add_bullets(doc, [
    "Busca y registra pacientes; crea citas y revisa la agenda.",
    "Abre Pagos y caja. Un pago nuevo empieza pendiente; solo aparece cobrado después de registrarlo y confirmarlo.",
    "Verifica el recibo imprimible y los métodos QR, efectivo, tarjeta, transferencia y opciones demostrativas.",
    "Abre Reportes y aplica filtros por rango, médico, especialidad y estado.",
])
add_heading(doc, "Administración", 2)
add_bullets(doc, [
    "Registra y edita médicos, asigna especialidades y cambia su estado.",
    "Configura especialidades, horarios e invitaciones de usuario.",
    "Supervisa reportes y, cuando corresponda, anula pagos con motivo.",
])

add_heading(doc, "7. Resultado de las pruebas automáticas", 1)
auto = [
    ("P01", "Registrar paciente", "Aprobada"), ("P02", "CI duplicado", "Aprobada"),
    ("P03", "Cita disponible", "Aprobada"), ("P04", "Cita superpuesta", "Aprobada"),
    ("P05", "Fuera de horario", "Aprobada"), ("P06", "Cancelar y conservar historial", "Aprobada"),
    ("P06B", "Liberar horario cancelado", "Aprobada"), ("P07", "Historial del paciente", "Aprobada"),
    ("P08", "Agenda por médico y fecha", "Aprobada"), ("P09", "Seguridad sin sesión", "Aprobada"),
    ("P10", "Validación de datos", "Aprobada"),
]
at = doc.add_table(rows=1, cols=3); at.alignment = WD_TABLE_ALIGNMENT.CENTER
for i,h in enumerate(("ID", "Comprobación", "Resultado")):
    shade(at.cell(0,i), NAVY); set_cell_text(at.cell(0,i), h, True, "FFFFFF")
for code, name, result in auto:
    cells=at.add_row().cells
    for i,v in enumerate((code,name,result)):
        shade(cells[i], LIGHT if len(at.rows)%2==0 else "FFFFFF"); set_cell_text(cells[i],v,i in (0,2),GREEN if i==2 else None)

add_heading(doc, "8. Observaciones y alcance", 1)
add_bullets(doc, [
    "Los pagos por PayPal u otras pasarelas externas se presentan como demostración visual; una integración real requiere credenciales y API del proveedor.",
    "Las notificaciones actuales registran cambios de las citas. Los recordatorios automáticos programados por correo o WhatsApp serían una mejora posterior.",
    "La exportación CSV o PDF de reportes es opcional en el documento. El comprobante de cita y el recibo de pago sí cuentan con versión imprimible/PDF.",
    "La publicación en GitHub debe hacerse únicamente cuando el propietario lo autorice y después de comprobar que .env, contraseñas y datos personales no se incluyan.",
])

add_heading(doc, "9. Lista rápida para mostrar a la ingeniera", 1)
check = [
    "Inicio de sesión y cuatro roles separados",
    "Paciente nuevo y rechazo de CI duplicado",
    "Reserva por pasos y médicos filtrados por especialidad",
    "Bloqueo de cruces y horarios inválidos",
    "Reprogramación, cancelación e historial",
    "Agenda del médico y ficha del paciente",
    "Pagos, QR y recibo imprimible",
    "Reportes con filtros y estadísticas",
    "Diseño adaptable en computadora y teléfono",
    "Cierre de sesión y protección de páginas internas",
]
for item in check:
    p = doc.add_paragraph()
    p.add_run("☐ ").bold = True
    p.add_run(item)

footer = doc.sections[0].footer.paragraphs[0]
footer.alignment = WD_ALIGN_PARAGRAPH.CENTER
run = footer.add_run("Clínica Vida Salud · Guía de verificación académica · 2026")
run.font.size = Pt(8); run.font.color.rgb = RGBColor.from_string(GRAY)

OUT.parent.mkdir(parents=True, exist_ok=True)
doc.save(OUT)
print(OUT)
from docx import Document
from docx.shared import Inches, Pt, RGBColor
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.enum.table import WD_TABLE_ALIGNMENT, WD_CELL_VERTICAL_ALIGNMENT
from docx.oxml import OxmlElement
from docx.oxml.ns import qn
from pathlib import Path

OUT = Path(__file__).resolve().parents[1] / "docs" / "Guia_de_pruebas_Clinica_Vida_Salud.docx"
TEAL, NAVY, MINT, GREEN, GRAY, LIGHT = "118D91", "16324F", "DDF4F1", "16845B", "5F6B76", "F3F7F9"

def shade(cell, fill):
    prop = cell._tc.get_or_add_tcPr(); node = prop.find(qn("w:shd"))
    if node is None: node = OxmlElement("w:shd"); prop.append(node)
    node.set(qn("w:fill"), fill)

def cell_text(cell, value, bold=False, color=None, size=9):
    cell.text = ""; p = cell.paragraphs[0]; r = p.add_run(str(value))
    r.bold = bold; r.font.name = "Aptos"; r.font.size = Pt(size)
    if color: r.font.color.rgb = RGBColor.from_string(color)
    cell.vertical_alignment = WD_CELL_VERTICAL_ALIGNMENT.CENTER

def heading(doc, text, level=1):
    p = doc.add_heading(text, level=level); p.paragraph_format.keep_with_next = True; return p

def bullets(doc, items, numbered=False):
    for item in items: doc.add_paragraph(item, style="List Number" if numbered else "List Bullet")

def test_block(doc, number, title, role, steps, expected):
    p = doc.add_paragraph(); p.paragraph_format.keep_with_next = True
    r = p.add_run(f"Prueba {number}. {title}"); r.bold = True; r.font.size = Pt(12); r.font.color.rgb = RGBColor.from_string(NAVY)
    p = doc.add_paragraph(); p.paragraph_format.keep_with_next = True
    r = p.add_run(f"Rol recomendado: {role}"); r.bold = True; r.font.color.rgb = RGBColor.from_string(TEAL)
    bullets(doc, steps, True)
    box = doc.add_table(rows=1, cols=1); shade(box.cell(0, 0), MINT)
    cell_text(box.cell(0, 0), "Resultado esperado: " + expected, True, NAVY, 9)
    doc.add_paragraph()

doc = Document(); sec = doc.sections[0]
sec.top_margin = Inches(.65); sec.bottom_margin = Inches(.65); sec.left_margin = Inches(.7); sec.right_margin = Inches(.7)
doc.styles["Normal"].font.name = "Aptos"; doc.styles["Normal"].font.size = Pt(10); doc.styles["Normal"].paragraph_format.space_after = Pt(5)
for name, size, color in (("Title",25,NAVY),("Heading 1",17,NAVY),("Heading 2",13,TEAL),("Heading 3",11,TEAL)):
    st=doc.styles[name]; st.font.name="Aptos Display"; st.font.size=Pt(size); st.font.color.rgb=RGBColor.from_string(color); st.font.bold=True

p=doc.add_paragraph(); p.alignment=WD_ALIGN_PARAGRAPH.CENTER; p.paragraph_format.space_before=Pt(18)
r=p.add_run("Guía de pruebas del sistema"); r.bold=True; r.font.name="Aptos Display"; r.font.size=Pt(25); r.font.color.rgb=RGBColor.from_string(NAVY)
p=doc.add_paragraph(); p.alignment=WD_ALIGN_PARAGRAPH.CENTER
r=p.add_run("Clínica Vida Salud · Examen 2 de Ingeniería de Requisitos"); r.font.size=Pt(12); r.font.color.rgb=RGBColor.from_string(TEAL)
p=doc.add_paragraph(); p.alignment=WD_ALIGN_PARAGRAPH.CENTER
r=p.add_run("Verificación realizada el 3 de noviembre de 2026"); r.italic=True; r.font.color.rgb=RGBColor.from_string(GRAY)

summary=doc.add_table(rows=2, cols=3); summary.alignment=WD_TABLE_ALIGNMENT.CENTER
for i,(h,v) in enumerate((("Pruebas mínimas","10/10 aprobadas"),("Regresiones","6/6 módulos aprobados"),("Datos de prueba","Revertidos automáticamente"))):
    shade(summary.cell(0,i),NAVY); cell_text(summary.cell(0,i),h,True,"FFFFFF")
    shade(summary.cell(1,i),LIGHT); cell_text(summary.cell(1,i),v,True,GREEN,10)

heading(doc,"1. Conclusión de la revisión")
doc.add_paragraph("El proyecto cumple las diez pruebas mínimas solicitadas en el documento de la ingeniera. También se verificaron los módulos de citas, roles, paciente, médico, invitaciones y pagos. La comprobación automática se ejecutó dentro de una transacción que se revirtió al finalizar, por lo que no dejó pacientes ni citas de prueba en la base de datos.")
doc.add_paragraph("Durante la revisión se completaron dos puntos incompletos: la edición de médicos y los filtros de reportes por médico, especialidad y estado, además del contador de pacientes registrados.")
heading(doc,"2. Preparación antes de probar")
bullets(doc,["Inicia XAMPP y confirma que Apache esté en ejecución.","Confirma que PostgreSQL esté iniciado.","Abre http://localhost/clinica-vida-salud/public/ en el navegador.","Usa datos de demostración; no uses información médica real.","Para reservas, utiliza una fecha futura y un horario disponible."])
heading(doc,"3. Cuentas de demostración")
t=doc.add_table(rows=1,cols=3); t.alignment=WD_TABLE_ALIGNMENT.CENTER
for i,h in enumerate(("Rol","Usuario","Contraseña")): shade(t.cell(0,i),NAVY); cell_text(t.cell(0,i),h,True,"FFFFFF")
for role,user,pwd in [("Paciente","paciente.demo@vidasalud.local","Paciente2026!"),("Médico","medico.demo@vidasalud.local","Medico2026!"),("Recepción","recepcion.demo@vidasalud.local","Recepcion2026!"),("Administración","admin","Contraseña administrativa actual")]:
    cells=t.add_row().cells
    for i,v in enumerate((role,user,pwd)): shade(cells[i],LIGHT if len(t.rows)%2==0 else "FFFFFF"); cell_text(cells[i],v,i==0,NAVY if i==0 else None)
heading(doc,"4. Matriz de cumplimiento")
rows=[("RF01","Acceso, sesión, roles y cierre","Cumple","Acceso separado y bloqueo interno"),("RF02","Pacientes y CI único","Cumple","Alta, búsqueda, edición, historial y estado"),("RF03","Gestión de médicos","Cumple","Alta, búsqueda, edición, agenda y estado"),("RF04","Especialidades","Cumple","Nombre único y normalizado"),("RF05","Horarios médicos","Cumple","Intervalos, duración y validación"),("RF06","Registro de citas","Cumple","Código único y disponibilidad"),("RF07","Evitar superposición","Cumple","Servidor y restricción PostgreSQL"),("RF08","Reprogramación","Cumple","Solo en estados permitidos"),("RF09","Cancelación","Cumple","Historial conservado y horario liberado"),("RF10","Estados de cita","Cumple","Transiciones controladas"),("RF11","Agenda médica","Cumple","Filtros por médico, fecha y estado"),("RF12","Historial del paciente","Cumple","Fecha, médico, especialidad y estado"),("RF13","Reportes","Cumple","Rango, médico, especialidad, estado y pacientes")]
mt=doc.add_table(rows=1,cols=4); mt.alignment=WD_TABLE_ALIGNMENT.CENTER
for i,h in enumerate(("Código","Requisito","Estado","Evidencia")): shade(mt.cell(0,i),NAVY); cell_text(mt.cell(0,i),h,True,"FFFFFF",8)
for vals in rows:
    cells=mt.add_row().cells
    for i,v in enumerate(vals): shade(cells[i],LIGHT if len(mt.rows)%2==0 else "FFFFFF"); cell_text(cells[i],v,i in (0,2),GREEN if i==2 else None,8)

doc.add_page_break(); heading(doc,"5. Pruebas paso a paso para la presentación")
test_block(doc,"1","Registrar un paciente nuevo","Recepción o administración",["Inicia sesión y abre Pacientes.","Pulsa Nuevo paciente.","Completa nombres, apellidos, CI único, fecha de nacimiento, sexo y contacto.","Pulsa Guardar paciente.","Busca el CI recién registrado."],"El sistema confirma el registro y el paciente aparece una sola vez.")
test_block(doc,"2","Rechazar un CI duplicado","Recepción o administración",["Abre Nuevo paciente.","Escribe el mismo CI de la prueba anterior y completa los campos obligatorios.","Intenta guardar."],"El sistema rechaza el registro e informa que el documento ya existe.")
test_block(doc,"3","Programar una cita disponible","Paciente o recepción",["Abre Solicitar cita o Nueva cita.","Selecciona la especialidad y pulsa Continuar.","Elige un médico; verifica que corresponda a la especialidad.","Selecciona fecha futura y una hora disponible.","Escribe un motivo de demostración, revisa y confirma."],"Se crea una cita con código único y estado Solicitada o Confirmada según el rol.")
test_block(doc,"4","Impedir una cita superpuesta","Recepción",["Intenta reservar otra cita para el mismo médico, fecha y hora de la prueba 3.","Completa el flujo hasta la validación final."],"El horario no se ofrece o el servidor rechaza la operación; nunca se guardan dos citas activas superpuestas.")
test_block(doc,"5","Rechazar una cita fuera del horario","Recepción",["Selecciona un médico y revisa sus horarios.","Intenta usar una fecha u hora fuera de su jornada; si no aparece, esa ausencia demuestra la validación visual."],"El horario no aparece y una petición inválida también es rechazada por servidor y base de datos.")
test_block(doc,"6","Cancelar sin borrar el historial","Paciente o recepción",["Abre el detalle de una cita de demostración.","Pulsa Cancelar, escribe un motivo y confirma.","Vuelve al historial del paciente."],"La cita queda como Cancelada, conserva el motivo y libera el horario.")
test_block(doc,"7","Consultar historial del paciente","Paciente, médico autorizado o recepción",["Busca al paciente de demostración.","Abre Historial o Ficha del paciente.","Revisa citas activas, atendidas y canceladas."],"Se muestran fecha, médico, especialidad, estado y creación, sin citas de otro paciente.")
test_block(doc,"8","Consultar citas por médico y fecha","Médico o recepción",["Abre Mi agenda o Agenda.","Selecciona una fecha que tenga citas.","En recepción, filtra por médico o estado."],"La agenda muestra solo las citas que coinciden con el médico y fecha elegidos.")
test_block(doc,"9","Bloquear una página interna sin sesión","Sin sesión",["Cierra sesión.","Escribe http://localhost/clinica-vida-salud/public/reportes.php en la barra del navegador."],"El sistema redirige al inicio de sesión y no muestra información interna.")
test_block(doc,"10","Rechazar una operación inválida","Recepción o administración",["Abre un formulario de paciente, médico, horario o cita.","Deja un dato obligatorio vacío o usa un formato inválido.","Pulsa Guardar."],"No se guarda y aparece un mensaje comprensible.")

heading(doc,"6. Pruebas complementarias por rol")
for title,items in [("Paciente",["Panel con próximas citas, realizadas y canceladas.","Reserva por pasos: especialidad, médico, fecha, hora y confirmación.","Mis citas, detalle, comprobante PDF y perfil.","Métodos de pago; PayPal y otros proveedores son demostrativos."]),("Médico",["Agenda, próximas consultas y pacientes relacionados.","Ficha del paciente desde una cita asignada.","Estados permitidos: confirmada, completada o no asistió.","Perfil, horarios y notificaciones."]),("Recepción",["Registro y búsqueda de pacientes, citas y agenda.","Pagos y caja: un pago nuevo empieza pendiente.","Recibo imprimible y métodos QR, efectivo, tarjeta y transferencia.","Reportes por rango, médico, especialidad y estado."]),("Administración",["Registrar y editar médicos, especialidades y estados.","Configurar especialidades, horarios e invitaciones.","Supervisar reportes y anular pagos con motivo."])]: heading(doc,title,2); bullets(doc,items)
heading(doc,"7. Resultado de las pruebas automáticas")
auto=[("P01","Registrar paciente"),("P02","CI duplicado"),("P03","Cita disponible"),("P04","Cita superpuesta"),("P05","Fuera de horario"),("P06","Cancelar y conservar historial"),("P06B","Liberar horario cancelado"),("P07","Historial del paciente"),("P08","Agenda por médico y fecha"),("P09","Seguridad sin sesión"),("P10","Validación de datos")]
at=doc.add_table(rows=1,cols=3); at.alignment=WD_TABLE_ALIGNMENT.CENTER
for i,h in enumerate(("ID","Comprobación","Resultado")): shade(at.cell(0,i),NAVY); cell_text(at.cell(0,i),h,True,"FFFFFF")
for code,name in auto:
    cells=at.add_row().cells
    for i,v in enumerate((code,name,"Aprobada")): shade(cells[i],LIGHT if len(at.rows)%2==0 else "FFFFFF"); cell_text(cells[i],v,i in (0,2),GREEN if i==2 else None)
heading(doc,"8. Observaciones y alcance")
bullets(doc,["PayPal y otras pasarelas externas son demostraciones visuales; una integración real requiere API y credenciales.","Las notificaciones actuales registran cambios de citas. Recordatorios programados por correo o WhatsApp son una mejora posterior.","La exportación CSV/PDF de reportes es opcional. El comprobante de cita y recibo de pago sí tienen versión imprimible/PDF.","Publicar en GitHub requiere autorización y revisión para excluir .env, contraseñas y datos personales."])
doc.add_page_break()
heading(doc,"9. Lista rápida para mostrar a la ingeniera")
for item in ["Inicio de sesión y cuatro roles separados","Paciente nuevo y rechazo de CI duplicado","Reserva por pasos y médicos filtrados por especialidad","Bloqueo de cruces y horarios inválidos","Reprogramación, cancelación e historial","Agenda del médico y ficha del paciente","Pagos, QR y recibo imprimible","Reportes con filtros y estadísticas","Diseño adaptable en computadora y teléfono","Cierre de sesión y protección interna"]:
    p=doc.add_paragraph(); p.add_run("☐ ").bold=True; p.add_run(item)
footer=doc.sections[0].footer.paragraphs[0]; footer.alignment=WD_ALIGN_PARAGRAPH.CENTER
r=footer.add_run("Clínica Vida Salud · Guía de verificación académica · 2026"); r.font.size=Pt(8); r.font.color.rgb=RGBColor.from_string(GRAY)
OUT.parent.mkdir(parents=True,exist_ok=True); doc.save(OUT); print(OUT)
