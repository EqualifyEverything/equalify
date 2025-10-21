import { create } from 'zustand'
import { persist } from 'zustand/middleware'

interface EqualifyState {
  loading: boolean|string
  setLoading: (val: string|boolean) => void
  darkMode: boolean
  setDarkMode: (val: boolean) => void
  authenticated: boolean
  setAuthenticated: (val: boolean) => void
  ariaAnnounceMessage: string
  setAriaAnnounceMessage: (val:string) => void
}

export const useGlobalStore = create<EqualifyState>()(
    persist(
        (set) => ({
            loading: false,
            setLoading: (val) => set(() => ({ loading: val })),
            darkMode: false,
            setDarkMode: (val) => set(() => ({ darkMode: val })),
            authenticated: false,
            setAuthenticated: (val) => set(() => ({ authenticated: val })),
            ariaAnnounceMessage: "",
            setAriaAnnounceMessage: (val) => set(() => ({ ariaAnnounceMessage: val })),
        }), {
        name: 'equalify-storage',
        partialize: (state) => ({
            darkMode: state.darkMode,
            authenticated: state.authenticated,
        })
    })
);