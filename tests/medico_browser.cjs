/* npm install playwright (fuera del proyecto también es válido).
 * PLAYWRIGHT_MODULE puede indicar la ruta absoluta del módulo instalado.
 * Cada ejecución usa un esquema PostgreSQL aislado y lo elimina al terminar.
 */
const { chromium } = require(process.env.PLAYWRIGHT_MODULE || 'playwright');
const { spawn, spawnSync } = require('node:child_process');
const { randomBytes } = require('node:crypto');
const path = require('node:path');
const net = require('node:net');
const assert = require('node:assert/strict');
const root = path.resolve(__dirname, '..');
const schema = 'test_medico_' + randomBytes(8).toString('hex');
const fixture = (action) => {
    const run = spawnSync('php', [path.join(__dirname,'medico_fixture.php'),action,schema], {cwd:root,encoding:'utf8'});
    if (run.status !== 0) throw new Error(run.stderr || run.stdout);
    return run.stdout;
};
const freePort = () => new Promise(resolve => { const server=net.createServer(); server.listen(0,'127.0.0.1',()=>{const port=server.address().port; server.close(()=>resolve(port));}); });
let server,browser,logs='';
(async()=>{
    const data=JSON.parse(fixture('setup'));
    const port=await freePort(); const base=`http://127.0.0.1:${port}/`;
    server=spawn('php',['-d','output_buffering=0','-d','display_errors=1','-S',`127.0.0.1:${port}`,'-t','public'],{cwd:root,env:{...process.env,PGOPTIONS:`-c search_path=${schema},public`}});
    server.stderr.on('data',chunk=>{logs+=chunk.toString();});
    server.stdout.on('data',chunk=>{logs+=chunk.toString();});
    for(let i=0;i<60;i++){try{if((await fetch(base+'login_moderno.php')).ok)break;}catch{} await new Promise(r=>setTimeout(r,100));}
    browser=await chromium.launch({headless:true});
    const errors=[];
    const context=await browser.newContext({viewport:{width:1366,height:1000}});
    const page=await context.newPage();
    page.on('pageerror',e=>errors.push(e.message));
    const screenshot=async(name)=>{if(process.env.TEST_ARTIFACT_DIR)await page.screenshot({path:path.join(process.env.TEST_ARTIFACT_DIR,name+'.png'),fullPage:true});};
    const fit=async(label)=>{
        const result=await page.evaluate(()=>({width:innerWidth,document:document.documentElement.scrollWidth}));
        assert.ok(result.document<=result.width+1,`${label}: desbordamiento horizontal ${JSON.stringify(result)}`);
        assert.equal(await page.locator('body').innerText().then(t=>/Fatal error|Warning:|Parse error|Deprecated:/.test(t)),false,label+': error PHP visible');
        const clipped=await page.locator('.page-content .card,.page-content .stat-card,.page-content .btn,.page-content .form-input').evaluateAll(elements=>elements.filter(el=>{
            const r=el.getBoundingClientRect(); return r.width>1 && (r.right>innerWidth+1 || r.left < -1);
        }).map(el=>el.className));
        assert.deepEqual(clipped,[],label+': elementos fuera de la pantalla');
    };
    for(const width of [1366,768,360]){
        await page.setViewportSize({width,height:1000}); await page.goto(base+'login_moderno.php');
        await fit('login '+width);
        const back=await page.locator('.back-home').boundingBox(),brand=await page.locator('.login-brand').boundingBox();
        assert.ok(brand.y-(back.y+back.height)>=24,'Separación entre volver y marca insuficiente');
        await screenshot('login-'+width);
        await page.goto(base+'index.php'); await fit('portada '+width);
        await page.goto(base+'activar_cuenta.php'); await fit('activación '+width);
    }
    const login=async(user)=>{
        await context.clearCookies(); await page.goto(base+'login_moderno.php');
        await page.locator('#usuario').fill(user); await page.locator('#contrasena').fill('PruebaMedico2026!');
        await Promise.all([page.waitForURL(/panel_.*\.php/),page.locator('.login-button').click()]);
    };
    const token=()=>page.locator('input[name="csrf_token"]').first().inputValue();
    const post=async(url,form)=>context.request.post(base+url,{form,maxRedirects:0});
    await login('medico');
    for(const width of [1366,768,360]){
        await page.setViewportSize({width,height:1000});
        for(const url of ['panel_medico.php','agenda.php?fecha='+data.ayer,'medico_pacientes.php','medico_paciente.php?id='+data.pacientes.paciente,'medico_perfil.php','ver_cita.php?id='+data.citas.propia,'notificaciones.php']){
            await page.goto(base+url); await fit(url+' '+width);
            if(['panel_medico.php','medico_perfil.php'].includes(url))await screenshot(url.replace('.php','')+'-'+width);
        }
    }
    await page.goto(base+'agenda.php?fecha='+data.ayer+'&medico_id='+data.medicos.otro_medico);
    assert.ok((await page.locator('body').innerText()).includes('TEST-PROPIA'));
    assert.ok(!(await page.locator('body').innerText()).includes('TEST-AJENA'));
    await page.goto(base+'ver_cita.php?id='+data.citas.propia+'&fecha='+data.ayer+'&estado=confirmada');
    assert.ok((await page.getByRole('link',{name:'Volver a mi agenda'}).getAttribute('href')).includes('estado=confirmada'));
    let csrf=await token();
    assert.equal((await context.request.get(base+'ver_cita.php?id='+data.citas.ajena)).status(),403);
    assert.equal((await post('cambiar_estado_cita.php',{csrf_token:csrf,id:data.citas.ajena,estado:'completada'})).status(),403);
    const future=await post('cambiar_estado_cita.php',{csrf_token:csrf,id:data.citas.futura,estado:'completada'});
    assert.equal(future.status(),302); assert.ok(future.headers().location.includes('error=fecha'));
    assert.equal((await post('cambiar_estado_cita.php',{csrf_token:'invalido',id:data.citas.propia,estado:'completada'})).status(),403);
    for(const url of ['horarios_moderno.php','registrar_horario.php','editar_horario.php?id=1'])assert.equal((await context.request.get(base+url)).status(),403,url);
    for(const url of ['guardar_horario.php','actualizar_horario.php','cambiar_estado_horario.php'])assert.equal((await post(url,{csrf_token:csrf,id:1,medico_id:data.medicos.otro_medico})).status(),403,url);
    await page.goto(base+'medico_paciente.php?id='+data.pacientes.paciente); csrf=await token();
    const clinical={csrf_token:csrf,paciente_id:data.pacientes.paciente,version:'',tipo_sangre:'O+',alergias:'Penicilina <script>window.clinicalInjection=true</script>',medicamentos:'',antecedentes:'Prueba de antecedentes',enfermedades_cronicas:''};
    assert.equal((await post('medico_guardar_informacion.php',{...clinical,paciente_id:data.pacientes.otro_paciente})).status(),403);
    assert.equal((await post('medico_guardar_informacion.php',clinical)).status(),302);
    const stale=await post('medico_guardar_informacion.php',clinical); assert.ok(stale.headers().location.includes('error=conflicto'));
    await page.goto(base+'medico_paciente.php?id='+data.pacientes.paciente);
    assert.equal(await page.evaluate(()=>window.clinicalInjection),undefined);
    assert.ok((await page.locator('body').innerText()).includes('Penicilina <script>'));
    await page.goto(base+'ver_cita.php?id='+data.citas.futura); assert.equal(await page.getByRole('button',{name:'Marcar como atendida'}).count(),0);
    await page.goto(base+'ver_cita.php?id='+data.citas.propia); csrf=await token();
    assert.equal((await post('cambiar_estado_cita.php',{csrf_token:csrf,id:data.citas.propia,estado:'completada'})).status(),302);
    await page.goto(base+'ver_cita.php?id='+data.citas.propia);
    assert.ok((await page.locator('.table').innerText()).includes('Prueba medico'));
    await page.goto(base+'notificaciones.php'); csrf=await token(); assert.equal((await post('notificaciones.php',{csrf_token:csrf})).status(),302);
    for(const user of ['sin_perfil','inactivo']){
        await login(user); await page.goto(base+'agenda.php?fecha='+data.ayer);
        const text=await page.locator('body').innerText(); assert.ok(!text.includes('TEST-PROPIA')&&!text.includes('TEST-AJENA'),user+' expone citas');
        assert.equal((await context.request.get(base+'ver_cita.php?id='+data.citas.propia)).status(),403);
    }
    await login('recepcion');
    await page.goto(base+'panel_recepcionista.php'); await fit('recepción móvil');
    for(const url of ['medicos_moderno.php','especialidades_moderno.php','horarios_moderno.php'])assert.equal((await context.request.get(base+url)).status(),403);
    await page.goto(base+'nueva_cita.php'); csrf=await token();
    const past=await post('guardar_cita.php',{csrf_token:csrf,paciente_id:data.pacientes.paciente,medico_id:data.medicos.medico,especialidad_id:1,fecha:data.ayer,hora_inicio:'09:00'});
    assert.equal(past.status(),302); assert.ok(past.headers().location.includes('error=pasado'));
    await login('paciente');
    await page.goto(base+'panel_paciente.php'); await fit('paciente móvil');
    assert.equal((await context.request.get(base+'ver_cita.php?id='+data.citas.ajena)).status(),403);
    assert.equal((await context.request.get(base+'medico_paciente.php?id='+data.pacientes.paciente)).status(),403);
    await login('admin');
    for(const width of [1366,768,360]){
        await page.setViewportSize({width,height:1000});
        for(const url of ['panel_administrador.php','reportes.php','auditoria.php']){await page.goto(base+url);await fit(url+' '+width);}
    }
    await page.setViewportSize({width:360,height:1000});
    for(const url of ['panel_administrador.php','pacientes_moderno.php','registrar_paciente.php','editar_paciente.php?id='+data.pacientes.paciente,'medicos_moderno.php','registrar_medico.php','horarios_moderno.php','registrar_horario.php','editar_horario.php?id=1','bloqueos_horario.php','especialidades_moderno.php','usuarios_moderno.php','nueva_cita.php','reprogramar_cita.php?id='+data.citas.futura,'cancelar_cita.php?id='+data.citas.futura,'reportes.php','auditoria.php']){await page.goto(base+url);await fit(url+' móvil');}
    await page.goto(base+'bloqueos_horario.php'); csrf=await token();
    let block=await post('bloqueos_horario.php',{csrf_token:csrf,medico_id:data.medicos.medico,motivo:'Ausencia de prueba',inicio:data.manana+'T09:00',fin:data.manana+'T10:00'});
    assert.ok(block.headers().location.includes('error=citas'),'No debe bloquear citas activas');
    block=await post('bloqueos_horario.php',{csrf_token:csrf,medico_id:data.medicos.medico,motivo:'Ausencia de prueba',inicio:data.manana+'T15:00',fin:data.manana+'T16:00'});
    assert.ok(block.headers().location.includes('creado=1'),'Debe registrar bloqueos sin citas');
    await page.goto(base+'editar_horario.php?id=1'); csrf=await token();
    const change=await post('actualizar_horario.php',{csrf_token:csrf,id:1,medico_id:data.medicos.medico,dia_semana:1,hora_inicio:'08:00',hora_fin:'17:00',intervalo_minutos:15});
    // La cita futura de la fixture puede caer en cualquier día: se prueba el
    // horario de ese día en la verificación de base de datos.
    assert.equal(change.status(),302);
    assert.deepEqual(errors,[],'Errores JavaScript');
    assert.ok(!/PHP (Warning|Fatal error|Parse error)/.test(logs),'Errores PHP en servidor: '+logs);
    console.log(fixture('verify'));
    console.log('Navegación, permisos, estados, edición clínica, móvil y login: OK');
})().catch(error=>{console.error(error);process.exitCode=1;}).finally(async()=>{
    if(browser)await browser.close(); if(server)server.kill();
    try{fixture('cleanup');}catch(error){console.error(error);process.exitCode=1;}
});
