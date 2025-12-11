import { toast } from 'sonner'
import { create } from 'zustand'
import { persist } from 'zustand/middleware'

interface EqualifyState {
  loading: boolean|string
  setLoading: (val: string|boolean) => void
  darkMode: boolean
  setDarkMode: (val: boolean) => void
  authenticated: boolean
  setAuthenticated: (val: boolean) => void
  ssoAuthenticated: boolean
  setSsoAuthenticated: (val: boolean) => void
  announceMessage: string
  setAnnounceMessage: (val:string, style?:"normal"|"success"|"error", screenReaderOnly?: boolean) => void
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
            ssoAuthenticated: false,
            setSsoAuthenticated: (val) => set(() => ({ ssoAuthenticated: val })),
            announceMessage: "",
            setAnnounceMessage: (val, style = "normal", setScreenReaderOnly = false) => { 
                set(() => ({ announceMessage: val })); 
                if(!setScreenReaderOnly){
                    switch(style){
                        case 'success': toast.success(val);
                            return;
                        case 'error': toast.error(val);
                            return;
                        default: toast(val);
                    }
                }
            },
        }), {
        name: 'equalify-storage',
        partialize: (state) => ({
            darkMode: state.darkMode,
            authenticated: state.authenticated,
            ssoAuthenticated: state.ssoAuthenticated,
        })
    })
);