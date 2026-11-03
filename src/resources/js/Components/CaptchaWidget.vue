<script setup>
/*
 * O captcha do login do painel (03/11/2026): o MESMO Altcha auto-hospedado
 * do formulário de contato — mesmo desafio (`/captcha/desafio`), mesma
 * verificação (`App\Support\Captcha\Altcha`), e o script em
 * `public/vendor/altcha/`, carregado pelo `app.blade.php`. Nada de terceiro.
 *
 * O widget avisa pelo evento `statechange`; quando chega a `verified`, o
 * `payload` é o token que o servidor confere (e queima: é de uso único).
 */
import { ref } from 'vue';

const emit = defineEmits(['update:modelValue']);
defineProps({ modelValue: { type: String, default: '' } });

const widget = ref(null);

function aoMudar(e) {
    emit('update:modelValue', e.detail?.state === 'verified' ? (e.detail.payload ?? '') : '');
}

// Depois de uma recusa o desafio já foi gasto: pede outro.
function reset() {
    widget.value?.reset?.();
    widget.value?.verify?.();
}

defineExpose({ reset });
</script>

<template>
    <altcha-widget
        ref="widget"
        @statechange="aoMudar"
        challenge="/captcha/desafio"
        language="pt-br"
        auto="onload"
        hidefooter
        hidelogo
    />
</template>
