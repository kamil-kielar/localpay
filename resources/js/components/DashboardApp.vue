<script setup>
import axios from 'axios';
import { computed, onMounted, reactive, ref } from 'vue';
import ChartCanvas from './ChartCanvas.vue';

const state = reactive({ organization: {}, plan: {}, billing: {}, entitlements: [], analytics: {}, properties: [], leases: [], obligations: [], revenues: [], notifications: [], invitations: [] });
const active = ref('dashboard');
const loading = ref(true);
const error = ref('');
const notice = ref('');
const propertyForm = reactive({ name: '', address: '', city: '', postal_code: '', purchase_cost_cents: 0, notes: '' });
const leaseForm = reactive({ property_id: '', tenant_name: '', tenant_email: '', tenant_phone: '', starts_at: '', ends_at: '', monthly_rent_cents: 0, due_day: 10, status: 'active' });
const obligationForm = reactive({ lease_id: '', period: '', due_date: '', amount_cents: 0, notes: '' });
const tabs = [
    ['dashboard', 'bi-grid', 'Pulpit'], ['properties', 'bi-buildings', 'Nieruchomości'],
    ['payments', 'bi-wallet2', 'Płatności'], ['leases', 'bi-people', 'Najemcy i umowy'],
    ['obligations', 'bi-receipt', 'Należności'], ['notifications', 'bi-bell', 'Powiadomienia'],
    ['billing', 'bi-credit-card', 'Plan i płatności'], ['settings', 'bi-gear', 'Ustawienia'],
];
const money = cents => new Intl.NumberFormat('pl-PL', { style: 'currency', currency: 'PLN' }).format(Number(cents || 0) / 100);
const list = value => value?.data || value || [];
const months = ['Sty', 'Lut', 'Mar', 'Kwi', 'Maj', 'Cze', 'Lip', 'Sie', 'Wrz', 'Paź', 'Lis', 'Gru'];
const monthly = computed(() => {
    const values = Array(12).fill(0);
    const year = new Date().getFullYear();
    state.revenues.forEach(p => { const d = new Date(p.paid_on); if (d.getFullYear() === year) values[d.getMonth()] += Number(p.amount_cents); });
    return values.map(v => v / 100);
});
const lineData = computed(() => ({ labels: months, datasets: [{ label: 'Przychód PLN', data: monthly.value, borderColor: '#3ee6a1', backgroundColor: 'rgba(62,230,161,.15)', fill: true, tension: .38 }] }));
const propertyData = computed(() => {
    const sums = Object.fromEntries(state.properties.map(p => [p.name, 0]));
    state.revenues.forEach(p => { sums[p.property] = (sums[p.property] || 0) + p.amount_cents / 100; });
    return { labels: Object.keys(sums), datasets: [{ label: 'Przychód PLN', data: Object.values(sums), backgroundColor: ['#3ee6a1', '#7867ff', '#38bdf8', '#fb7185', '#fbbf24'] }] };
});
const doughnutData = computed(() => ({ labels: ['Odzyskano', 'Pozostało'], datasets: [{ data: [state.analytics.revenue_cents || 0, state.analytics.remaining_cents || 0], backgroundColor: ['#3ee6a1', '#253047'], borderWidth: 0 }] }));
const statusClass = status => ({ paid: 'success', overdue: 'danger', partial: 'warning', due: 'info', void: 'secondary' }[status] || 'secondary');

async function load() {
    loading.value = true; error.value = '';
    try {
        const { data } = await axios.get('/api/dashboard');
        Object.assign(state, data, { properties: list(data.properties), leases: list(data.leases), obligations: list(data.obligations), revenues: list(data.revenues), notifications: list(data.notifications), invitations: list(data.invitations) });
    } catch (e) { error.value = e.response?.data?.message || 'Nie udało się pobrać danych.'; }
    finally { loading.value = false; }
}
async function run(action, message) {
    error.value = ''; notice.value = '';
    try { await action(); notice.value = message; await load(); }
    catch (e) { error.value = Object.values(e.response?.data?.errors || {}).flat()[0] || e.response?.data?.message || 'Operacja nie powiodła się.'; }
}
const addProperty = () => run(async () => {
    await axios.post('/api/properties', { ...propertyForm, purchase_cost_cents: Number(propertyForm.purchase_cost_cents) });
    Object.assign(propertyForm, { name: '', address: '', city: '', postal_code: '', purchase_cost_cents: 0, notes: '' });
}, 'Nieruchomość została dodana.');
const archiveProperty = id => run(() => axios.delete(`/api/properties/${id}`), 'Nieruchomość została zarchiwizowana.');
const editProperty = property => {
    const name = window.prompt('Nazwa nieruchomości', property.name);
    if (!name) return;
    const cost = Number(window.prompt('Koszt zakupu w groszach', property.purchase_cost_cents));
    if (!Number.isFinite(cost) || cost < 0) return;
    run(() => axios.put(`/api/properties/${property.id}`, { ...property, name, purchase_cost_cents: cost }), 'Nieruchomość została zaktualizowana.');
};
const addLease = () => run(async () => {
    await axios.post('/api/leases', { ...leaseForm, monthly_rent_cents: Number(leaseForm.monthly_rent_cents), due_day: Number(leaseForm.due_day), ends_at: leaseForm.ends_at || null, tenant_email: leaseForm.tenant_email || null, tenant_phone: leaseForm.tenant_phone || null });
}, 'Umowa została utworzona.');
const invite = id => run(() => axios.post(`/api/leases/${id}/invite`), 'Zaproszenie zostało wysłane.');
const quickLink = async id => {
    try {
        const { data } = await axios.post(`/api/leases/${id}/quick-link`);
        await navigator.clipboard?.writeText(data.url);
        window.prompt('Skopiuj bezpieczny link ważny 90 dni. LokalPay nie wysyła SMS:', data.url);
    } catch (e) { error.value = e.response?.data?.message || 'Nie udało się utworzyć linku.'; }
};
const revokeInvite = id => run(() => axios.post(`/api/invitations/${id}/revoke`), 'Zaproszenie zostało odwołane.');
const generate = id => run(() => axios.post(`/api/leases/${id}/generate-obligations`), 'Harmonogram został wygenerowany.');
const addObligation = () => run(() => axios.post('/api/obligations', { ...obligationForm, amount_cents: Number(obligationForm.amount_cents) }), 'Należność została dodana.');
const offline = obligation => {
    const amount = Number(window.prompt('Kwota wpłaty w groszach', obligation.remaining_cents));
    if (!amount) return;
    run(() => axios.post(`/api/obligations/${obligation.id}/offline-payment`, { amount_cents: amount, paid_on: new Date().toISOString().slice(0, 10), note: 'Wpłata ręczna z panelu' }), 'Wpłata została zapisana.');
};
const checkoutPlan = (plan, provider) => run(async () => {
    const { data } = await axios.post('/api/billing/checkout', { plan, provider });
    window.location.assign(data.checkout_url);
}, 'Przekierowanie do operatora płatności.');
async function logout() { await axios.post('/wyloguj'); window.location.assign('/'); }
onMounted(() => {
    const selectedPlan = new URLSearchParams(window.location.search).get('plan');
    if (['growth', 'pro'].includes(selectedPlan)) active.value = 'billing';
    load();
});
</script>
<template>
<div class="dashboard-shell">
    <aside class="app-sidebar">
        <a class="app-brand" href="/"><span class="brand-mark">L</span><span>LokalPay <b>Pro</b></span></a>
        <nav aria-label="Menu panelu"><button v-for="tab in tabs" :key="tab[0]" :class="{active: active === tab[0]}" @click="active = tab[0]"><i :class="`bi ${tab[1]}`"></i><span>{{ tab[2] }}</span></button></nav>
        <div class="sidebar-plan"><small>TWÓJ PLAN</small><b>{{ state.plan.name || 'Free' }}</b><span>{{ state.properties.length }} / {{ state.plan.property_limit || 3 }} nieruchomości</span><div class="progress"><div class="progress-bar" :style="{width: `${Math.min(100, state.properties.length / (state.plan.property_limit || 3) * 100)}%`}"></div></div></div>
        <button class="logout" @click="logout"><i class="bi bi-box-arrow-left"></i> Wyloguj</button>
    </aside>
    <main class="app-main">
        <header class="app-topbar"><div><small>{{ state.organization.name }}</small><h1>{{ tabs.find(t => t[0] === active)?.[2] }}</h1></div><div class="top-actions"><button @click="active='notifications'" aria-label="Powiadomienia"><i class="bi bi-bell"></i><span v-if="state.notifications.some(n=>!n.read_at)" class="dot"></span></button><span class="avatar">{{ state.organization.name?.slice(0,2).toUpperCase() }}</span></div></header>
        <div v-if="error" class="alert alert-danger">{{ error }}</div><div v-if="notice" class="alert alert-success">{{ notice }}</div>
        <div v-if="loading" class="loading-state"><div class="spinner-border text-success"></div></div>
        <template v-else>
            <section v-if="active==='dashboard'">
                <div class="kpi-grid"><article><i class="bi bi-buildings"></i><small>Wartość portfela</small><strong>{{ money(state.analytics.portfolio_value_cents) }}</strong><span>{{ state.properties.length }} nieruchomości</span></article><article><i class="bi bi-graph-up"></i><small>Przychód łącznie</small><strong>{{ money(state.analytics.revenue_cents) }}</strong><span>ROI {{ state.analytics.roi_percent }}%</span></article><article><i class="bi bi-pie-chart"></i><small>Odzyskany kapitał</small><strong>{{ state.analytics.recovery_percent }}%</strong><span>Pozostało {{ money(state.analytics.remaining_cents) }}</span></article><article><i class="bi bi-exclamation-circle"></i><small>Zaległości</small><strong>{{ state.obligations.filter(o=>o.status==='overdue').length }}</strong><span>należności po terminie</span></article></div>
                <div class="row g-4 mt-1"><div class="col-xl-8"><article class="panel-card"><h2>Przychód w tym roku</h2><ChartCanvas :data="lineData"/></article></div><div class="col-xl-4"><article class="panel-card"><h2>Zwrot portfela</h2><ChartCanvas type="doughnut" :data="doughnutData"/></article></div><div class="col-12"><article class="panel-card"><h2>Przychód według nieruchomości</h2><ChartCanvas type="bar" :data="propertyData"/></article></div></div>
            </section>
            <section v-if="active==='properties'"><div class="section-toolbar"><div><h2>Twój portfel</h2><p>Dodawaj, edytuj i archiwizuj nieruchomości zgodnie z limitem planu.</p></div></div><form class="panel-card form-grid" @submit.prevent="addProperty"><input v-model="propertyForm.name" class="form-control" placeholder="Nazwa lokalu" required><input v-model="propertyForm.address" class="form-control" placeholder="Adres" required><input v-model="propertyForm.city" class="form-control" placeholder="Miasto" required><input v-model="propertyForm.postal_code" class="form-control" placeholder="Kod pocztowy"><input v-model="propertyForm.purchase_cost_cents" type="number" min="0" class="form-control" placeholder="Koszt zakupu (grosze)" required><button class="btn btn-lime">Dodaj nieruchomość</button></form><div class="property-grid mt-4"><article v-for="p in state.properties" :key="p.id" class="panel-card property-tile"><span class="badge text-bg-dark">{{ p.city }}</span><h3>{{ p.name }}</h3><p>{{ p.address }}</p><strong>{{ money(p.purchase_cost_cents) }}</strong><small>{{ p.leases_count }} umów</small><div class="d-flex gap-2 mt-3"><button class="btn btn-sm btn-outline-primary" @click="editProperty(p)">Edytuj</button><button class="btn btn-sm btn-outline-danger" @click="archiveProperty(p.id)">Archiwizuj</button></div></article></div></section>
            <section v-if="active==='payments'"><article class="panel-card"><h2>Historia przychodów</h2><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Data</th><th>Nieruchomość</th><th>Źródło</th><th>Kwota</th></tr></thead><tbody><tr v-for="p in state.revenues" :key="p.id"><td>{{ p.paid_on }}</td><td>{{ p.property }}</td><td><span class="badge text-bg-light">{{ p.source }}</span></td><td class="fw-bold">{{ money(p.amount_cents) }}</td></tr></tbody></table></div></article></section>
            <section v-if="active==='leases'"><form class="panel-card form-grid" @submit.prevent="addLease"><select v-model="leaseForm.property_id" class="form-select" required><option value="">Wybierz nieruchomość</option><option v-for="p in state.properties" :value="p.id">{{ p.name }}</option></select><input v-model="leaseForm.tenant_name" class="form-control" placeholder="Najemca" required><input v-model="leaseForm.tenant_email" type="email" class="form-control" placeholder="E-mail"><input v-model="leaseForm.tenant_phone" class="form-control" placeholder="Telefon (metadane, bez SMS)"><input v-model="leaseForm.starts_at" type="date" class="form-control" required><input v-model="leaseForm.ends_at" type="date" class="form-control"><input v-model="leaseForm.monthly_rent_cents" type="number" min="1" class="form-control" placeholder="Czynsz (grosze)" required><input v-model="leaseForm.due_day" type="number" min="1" max="28" class="form-control"><button class="btn btn-lime">Utwórz umowę</button></form><div class="table-responsive panel-card mt-4"><table class="table"><thead><tr><th>Lokal</th><th>Najemca</th><th>Czynsz</th><th>Status</th><th>Akcje</th></tr></thead><tbody><tr v-for="l in state.leases" :key="l.id"><td>{{ l.property_name }}</td><td>{{ l.tenant_name }}<small class="d-block">{{ l.tenant_email || l.tenant_phone }}</small></td><td>{{ money(l.monthly_rent_cents) }}</td><td>{{ l.status }}</td><td><button v-if="l.tenant_email" class="btn btn-sm btn-outline-success me-2" @click="invite(l.id)">Wyślij / ponów e-mail</button><button class="btn btn-sm btn-outline-secondary me-2" @click="quickLink(l.id)">Link 90 dni</button><button class="btn btn-sm btn-outline-primary" @click="generate(l.id)">Generuj należności</button></td></tr></tbody></table></div><article class="panel-card mt-4"><h2>Zaproszenia</h2><div v-for="i in state.invitations" :key="i.id" class="notification-row"><i class="bi bi-envelope"></i><div class="flex-grow-1"><b>{{ i.property }} — {{ i.email }}</b><p>Status: {{ i.status }} · ważne do {{ i.expires_at }}</p></div><button v-if="i.status==='pending'" class="btn btn-sm btn-outline-danger" @click="revokeInvite(i.id)">Odwołaj</button></div></article></section>
            <section v-if="active==='obligations'"><form class="panel-card form-grid" @submit.prevent="addObligation"><select v-model="obligationForm.lease_id" class="form-select" required><option value="">Wybierz umowę</option><option v-for="l in state.leases" :value="l.id">{{ l.property_name }} — {{ l.tenant_name }}</option></select><input v-model="obligationForm.period" type="month" class="form-control" required><input v-model="obligationForm.due_date" type="date" class="form-control" required><input v-model="obligationForm.amount_cents" type="number" min="1" class="form-control" placeholder="Kwota (grosze)" required><button class="btn btn-lime">Dodaj należność</button></form><div class="table-responsive panel-card mt-4"><table class="table"><thead><tr><th>Okres</th><th>Lokal / najemca</th><th>Termin</th><th>Kwota</th><th>Status</th><th></th></tr></thead><tbody><tr v-for="o in state.obligations" :key="o.id"><td>{{ o.period }}</td><td>{{ o.property_name }}<small class="d-block">{{ o.tenant_name }}</small></td><td>{{ o.due_date }}</td><td>{{ money(o.remaining_cents) }} / {{ money(o.amount_cents) }}</td><td><span class="badge" :class="`text-bg-${statusClass(o.status)}`">{{ o.status }}</span></td><td><button v-if="!['paid','void'].includes(o.status)" class="btn btn-sm btn-outline-success" @click="offline(o)">Zapisz wpłatę</button></td></tr></tbody></table></div></section>
            <section v-if="active==='notifications'"><article class="panel-card"><h2>Centrum powiadomień</h2><div v-for="n in state.notifications" :key="n.id" class="notification-row" :class="{unread: !n.read_at}"><i class="bi bi-bell"></i><div><b>{{ n.data?.title || 'Powiadomienie' }}</b><p>{{ n.data?.body }}</p><small>{{ n.created_at }}</small></div></div><p v-if="!state.notifications.length">Brak powiadomień.</p></article></section>
            <section v-if="active==='billing'"><div class="d-flex justify-content-end mb-3"><a v-if="state.billing.has_stripe_customer" href="/api/billing/portal" class="btn btn-outline-dark">Portal rozliczeniowy Stripe</a></div><div class="row g-4"><div v-for="plan in [{code:'free',name:'Free',price:0,limit:3},{code:'growth',name:'Growth',price:49,limit:10},{code:'pro',name:'Pro',price:129,limit:50}]" class="col-md-4"><article class="panel-card price-admin" :class="{current: state.plan.code===plan.code}"><span v-if="state.plan.code===plan.code" class="badge text-bg-success">AKTUALNY</span><h2>{{ plan.name }}</h2><div class="display-5 fw-bold">{{ plan.price }} zł</div><p>do {{ plan.limit }} nieruchomości</p><template v-if="plan.price"><button class="btn btn-dark w-100 mb-2" @click="checkoutPlan(plan.code,'stripe')">Subskrybuj przez Stripe</button><button class="btn btn-outline-primary w-100" @click="checkoutPlan(plan.code,'payu')">PayU — 30 dni, bez odnowienia</button></template></article></div></div></section>
            <section v-if="active==='settings'"><article class="panel-card"><h2>Ustawienia organizacji</h2><dl class="row mt-4"><dt class="col-sm-4">Nazwa</dt><dd class="col-sm-8">{{ state.organization.name }}</dd><dt class="col-sm-4">Identyfikator</dt><dd class="col-sm-8"><code>{{ state.organization.id }}</code></dd><dt class="col-sm-4">Status</dt><dd class="col-sm-8">{{ state.organization.status }}</dd><dt class="col-sm-4">Funkcje planu</dt><dd class="col-sm-8">{{ state.entitlements.join(', ') }}</dd></dl><p class="text-secondary">Dane konta, sesje i hasło korzystają ze standardowych mechanizmów Laravel.</p></article></section>
        </template>
    </main>
</div>
</template>
