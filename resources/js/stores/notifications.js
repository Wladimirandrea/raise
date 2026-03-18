// resources/js/stores/notifications.js
import { defineStore } from 'pinia'

export const useNotificationsStore = defineStore('notifications', {
  state: () => ({
    newUsers:        [],
    newAppointments: [],
  }),

  getters: {
    newUsersCount:        (state) => state.newUsers.length,
    newAppointmentsCount: (state) => state.newAppointments.length,
  },

  actions: {
    addUser(user) {
      this.newUsers.unshift(user)
    },
    addAppointment(appointment) {
      this.newAppointments.unshift(appointment)
    },
    resetUsers() {
      this.newUsers = []
    },
    resetAppointments() {
      this.newAppointments = []
    },
    reset() {
      this.newUsers = []
      this.newAppointments = []
    },
  }
})