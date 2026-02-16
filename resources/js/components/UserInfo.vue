<script setup lang="ts">
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/composables/useInitials';
import type { User } from '@/types';
import { computed } from 'vue';

interface Props {
    user?: User | null;
    showEmail?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    user: null,
    showEmail: false,
});

const { getInitials } = useInitials();

// Safe check for a valid avatar URL
const hasValidAvatar = computed(() => 
    !!props.user?.avatar && props.user.avatar !== ''
);
</script>

<template>
    <Avatar class="h-8 w-8 overflow-hidden rounded-lg">
        <!-- 
            Only render AvatarImage when we definitely have a valid avatar.
            The type assertions are safe because hasValidAvatar guarantees 
            user, user.avatar, and user.name are truthy strings.
        -->
        <AvatarImage 
            v-if="hasValidAvatar" 
            :src="(props.user!.avatar as string)" 
            :alt="(props.user!.name as string)" 
        />
        <AvatarFallback class="rounded-lg text-black">
            {{ props.user ? getInitials(props.user.name) : 'G' }}
        </AvatarFallback>
    </Avatar>

    <div class="grid flex-1 text-left text-sm leading-tight">
        <span class="truncate font-medium">
            {{ props.user?.name || 'Guest' }}
        </span>
        <span 
            v-if="showEmail" 
            class="truncate text-xs text-muted-foreground"
        >
            {{ props.user?.email || 'guest@example.com' }}
        </span>
    </div>
</template>