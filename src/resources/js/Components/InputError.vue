<script setup>
import { AlertCircle } from 'lucide-vue-next';

defineProps({ message: { type: String, default: null } });
</script>

<template>
    <!-- `role="alert"`: erro de validação precisa ser anunciado por leitor de
         tela quando aparece, não só quando alguém navega até ele. -->
    <p v-show="message" role="alert" class="mt-1 flex items-start gap-1.5 text-sm text-danger">
        <AlertCircle class="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />
        <span>{{ message }}</span>
    </p>
</template>
