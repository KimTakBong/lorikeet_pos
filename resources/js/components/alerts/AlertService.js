import Swal from 'sweetalert2';

export const AlertService = {
  success(message, title = 'Success') {
    return Swal.fire({ icon: 'success', title, text: message, confirmButtonColor: '#4f46e5', confirmButtonText: 'OK' });
  },
  error(message, title = 'Error') {
    return Swal.fire({ icon: 'error', title, text: message, confirmButtonColor: '#dc2626', confirmButtonText: 'OK' });
  },
  warning(message, title = 'Warning') {
    return Swal.fire({ icon: 'warning', title, text: message, confirmButtonColor: '#f59e0b', confirmButtonText: 'OK' });
  },
  async confirm(message, title = 'Confirm') {
    const result = await Swal.fire({ icon: 'question', title, text: message, showCancelButton: true, confirmButtonColor: '#4f46e5', cancelButtonColor: '#6b7280', confirmButtonText: 'Yes', cancelButtonText: 'Cancel' });
    return result.isConfirmed;
  },
  async deleteConfirm(itemName = 'this item') {
    return this.confirm(`Are you sure you want to delete ${itemName}? This action cannot be undone.`, 'Delete Confirmation');
  }
};

export default AlertService;
