import { onMounted, onUnmounted } from 'vue';

export function useEscListener(callback, enabled = true) {
  const handleEsc = (event) => {
    if (event.key === 'Escape' && enabled) {
      event.preventDefault();
      callback();
    }
  };

  onMounted(() => {
    document.addEventListener('keydown', handleEsc);
  });

  onUnmounted(() => {
    document.removeEventListener('keydown', handleEsc);
  });
}

export default useEscListener;
