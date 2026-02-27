<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import BusinessSettingLayout from '@/layouts/settings/BusinessSettingLayout.vue';

type Props = {
    whatsapp: {
        delivery_man_phone: string;
        delivery_man_message: string;
    };
    status?: string;
};

const props = defineProps<Props>();

// Inertia form
const form = useForm({
    delivery_man_phone: props.whatsapp?.delivery_man_phone ?? '',
    delivery_man_message: props.whatsapp?.delivery_man_message ?? '',
});

function submit() {
    form.put(route('business-settings.whatsapp.update'));
}
</script>

<template>
    <AppLayout>
        <Head title="Whatsapp settings" />

        <BusinessSettingLayout>
            <div class="flex flex-col space-y-6">

                <Heading
                    variant="small"
                    title="Whatsapp settings"
                    description="Add delivery man number and default message"
                />

                <!-- Phone -->
                <div class="grid gap-2">

                    <Label>Delivery man phone</Label>

                    <Input
                        v-model="form.delivery_man_phone"
                        placeholder="8801XXXXXXXXX"
                    />

                    <InputError :message="form.errors.delivery_man_phone" />

                </div>


                <!-- Message -->
                <div class="grid gap-2">

                    <Label>Default message</Label>

                    <textarea
                        v-model="form.delivery_man_message"
                        rows="6"
                        class="mt-1 block w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        placeholder="Write your default WhatsApp message..."
                    />

                    <InputError :message="form.errors.delivery_man_message" />

                </div>


                <!-- Save -->
                <div class="flex items-center gap-4">

                    <Button
                        @click="submit"
                        :disabled="form.processing"
                    >
                        Save
                    </Button>

                    <p v-if="form.recentlySuccessful" class="text-sm text-green-600">
                        Saved.
                    </p>

                    <p v-if="status==='whatsapp-updated'" class="text-sm text-green-600">
                        Settings updated.
                    </p>

                </div>


            </div>
        </BusinessSettingLayout>

    </AppLayout>
</template>
