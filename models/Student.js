const mongoose = require('mongoose');

const studentSchema = new mongoose.Schema({
  name: {
    type: String,
    required: true,
    trim: true
  },
  fatherName: {
    type: String,
    required: true,
    trim: true
  },
  gender: {
    type: String,
    enum: ['M', 'F'],
    required: true
  },
  halqaId: {
    type: mongoose.Schema.Types.ObjectId,
    ref: 'Halqa',
    required: true
  },
  shuba: {
    type: String,
    enum: ['Hifz', 'Qaidah', 'Nazira'],
    required: true
  },
  admissionDate: {
    type: Date,
    default: Date.now
  },
  status: {
    type: String,
    enum: ['active', 'inactive'],
    default: 'active'
  },
  academicYear: {
    type: String,
    default: '2025-2026'
  },
  attendancePercentage: {
    type: Number,
    default: 100
  },
  mumayyizStatus: {
    type: Boolean,
    default: false
  },
  createdAt: {
    type: Date,
    default: Date.now
  },
  updatedAt: {
    type: Date,
    default: Date.now
  }
});

module.exports = mongoose.model('Student', studentSchema);
