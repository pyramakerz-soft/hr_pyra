import { Injectable } from '@angular/core';

@Injectable({
  providedIn: 'root'
})
export class HrStateService {
  private hrEmployeeState = {
    searchQuery: '',
    currentPage: 1,
    tableData: [],
    pagesNumber: 1
  };

  private hrAttendanceState = {
    searchQuery: '',
    currentPage: 1,
    tableData: [],
    pagesNumber: 1,
    from_day: '',
    to_day: '',
    selectedDepartment: null,
    selectedSubDepartmentIds: [],
    displayPagination: true
  };

  saveEmployeeState(state: any) {
    this.hrEmployeeState = { ...this.hrEmployeeState, ...state };
  }

  getEmployeeState() {
    return { ...this.hrEmployeeState };
  }

  clearEmployeeState() {
    this.hrEmployeeState = {
      searchQuery: '',
      currentPage: 1,
      tableData: [],
      pagesNumber: 1
    };
  }

  saveAttendanceState(state: any) {
    this.hrAttendanceState = { ...this.hrAttendanceState, ...state };
  }

  getAttendanceState() {
    return { ...this.hrAttendanceState };
  }

  clearAttendanceState() {
    this.hrAttendanceState = {
      searchQuery: '',
      currentPage: 1,
      tableData: [],
      pagesNumber: 1,
      from_day: '',
      to_day: '',
      selectedDepartment: null,
      selectedSubDepartmentIds: [],
      displayPagination: true
    };
  }
}