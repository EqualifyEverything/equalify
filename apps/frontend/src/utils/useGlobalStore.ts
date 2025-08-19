import { create } from 'zustand'
import { persist } from 'zustand/middleware'

export const useGlobalStore = create(
    persist(
        (set) => ({
            loading: false,
            setLoading: (value) => set(() => ({ loading: value })),
            darkMode: false,
            setDarkMode: (value) => set(() => ({ darkMode: value })),
            authenticated: false,
            setAuthenticated: (value) => set(() => ({ authenticated: value })),
        }), {
        name: 'equalify-storage',
        partialize: (state) => ({
            darkMode: state.darkMode,
            authenticated: state.authenticated,
        })
    })
);