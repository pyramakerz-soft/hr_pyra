import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { AddEmployee } from '../Models/add-employee';
import { UserModel } from '../Models/user-model';
import { ApiService } from './api.service';
import { HrUserProfile, HrUserProfileUpdatePayload } from '../Models/hr-user-profile';

@Injectable({
  providedIn: 'root'
})
export class UserServiceService {

  baseURL = ""

  constructor(public http: HttpClient, public Api: ApiService) {
    this.baseURL = Api.BaseUrl

  }
  getall(pageNumber: number, from_day?: string, to_day?: string, options?: { allDepartments?: boolean; departmentId?: number | 'none'; subDepartmentIds?: number[]; }): Observable<UserModel[]> {
    const token = localStorage.getItem("token");
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);

    let params: any = {};
    if (!options?.allDepartments) {
      params.page = pageNumber;
    }
    if (from_day) params.from_day = from_day;
    if (to_day) params.to_day = to_day;
    if (options?.allDepartments) {
      params.all_departments = true;
    }
    if (options?.departmentId !== undefined && options.departmentId !== null) {
      params.department_id = options.departmentId;
    }
    if (options?.subDepartmentIds && options.subDepartmentIds.length > 0) {
      params.sub_department_ids = options.subDepartmentIds.join(',');
    }

    return this.http.get<UserModel[]>(this.baseURL + `/users/getAllUsers`, { headers, params });
  }

  getUserById(id: number): Observable<AddEmployee> {
    const token = localStorage.getItem("token");
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);
    return this.http.get<AddEmployee>(this.baseURL + "/users/get_user_by_id/" + id, { headers });
  }

  createUser(emp: AddEmployee) {
    const token = localStorage.getItem("token");
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);

    let formData = new FormData();
    formData.append('image', emp.image as File);
    formData.append('name', emp.name);
    formData.append('code', emp.code || '');
    formData.append('department_id', emp.department_id?.toString() || '');
    formData.append('sub_department_id', emp.sub_department_id?.toString() || '');
    formData.append('timezone_id', emp.timezone_id?.toString() || '');

    formData.append('emp_type', emp.emp_type);
    formData.append('phone', emp.phone);
    formData.append('contact_phone', emp.contact_phone);
    formData.append('email', emp.email);
    formData.append('password', emp.password);
    formData.append('national_id', emp.national_id);
    formData.append('hiring_date', emp.hiring_date ? emp.hiring_date.toString() : '');
    formData.append('salary', emp.salary?.toString() || '');
    formData.append('overtime_hours', emp.overtime_hours?.toString() || '');
    formData.append('working_hours_day', emp.working_hours_day?.toString() || '');
    formData.append('max_monthly_hours', emp.max_monthly_hours !== null && emp.max_monthly_hours !== undefined ? emp.max_monthly_hours.toString() : '');
    formData.append('start_time', emp.start_time || '');
    formData.append('end_time', emp.end_time || '');
    formData.append('gender', emp.gender);
    formData.append('role', emp.role?.name.toString() || '');
    formData.append('is_part_time', emp.is_part_time ? '1' : '0');
    formData.append('bank_name', emp.bank_name || '');
    formData.append('bank_account_number', emp.bank_account_number || '');
    if (emp.works_on_saturday === null || emp.works_on_saturday === undefined) {
      formData.append('works_on_saturday', '');
    } else {
      formData.append('works_on_saturday', emp.works_on_saturday ? '1' : '0');
    }


    // emp.roles.forEach((role, index) => formData.append(`roles[${index}]`, role));
    emp.location_id.forEach((id, index) => formData.append(`location_id[${index}]`, id.toString()));
    emp.work_type_id.forEach((id, index) => formData.append(`work_type_id[${index}]`, id.toString()));

    return this.http.post<any>(this.baseURL + "/users/create_user", formData, { headers });
  }

  importVacationBalances(file: File): Observable<any> {
    const token = localStorage.getItem("token");
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);
    const formData = new FormData();
    formData.append('file', file, file.name);
    return this.http.post(`${this.baseURL}/vacation/import-vacation-balances`, formData, { headers });
  }

  updateUser(emp: AddEmployee, empId: number) {
    const token = localStorage.getItem("token");
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);

    let formData = new FormData();

    // Handle the image field
    if (typeof emp.image === "string") {
      // If the image is a string (URL), skip appending it to FormData
      // You might need to handle this differently on the server
    } else if (emp.image instanceof File) {
      // If the image is a file, append it to FormData
      formData.append('image', emp.image);
    }

    // Append other fields
    formData.append('name', emp.name || '');
    formData.append('code', emp.code || '');
    formData.append('department_id', emp.department_id?.toString() || '');
    formData.append('sub_department_id', emp.sub_department_id?.toString() || '');
    formData.append('timezone_id', emp.timezone_id?.toString() || '');

    formData.append('emp_type', emp.emp_type || '');
    formData.append('phone', emp.phone || '');
    formData.append('contact_phone', emp.contact_phone || '');
    formData.append('email', emp.email || '');
    formData.append('national_id', emp.national_id || '');
    formData.append('hiring_date', emp.hiring_date ? emp.hiring_date.toString() : '');
    formData.append('salary', emp.salary?.toString() || '');
    formData.append('overtime_hours', emp.overtime_hours?.toString() || '');
    formData.append('working_hours_day', emp.working_hours_day?.toString() || '');
    formData.append('max_monthly_hours', emp.max_monthly_hours !== null && emp.max_monthly_hours !== undefined ? emp.max_monthly_hours.toString() : '');
    formData.append('start_time', emp.start_time || '');
    formData.append('end_time', emp.end_time || '');
    formData.append('gender', emp.gender || '');
    formData.append('role', emp.role?.name.toString() || '');
    formData.append('is_part_time', emp.is_part_time ? '1' : '0');
    formData.append('bank_name', emp.bank_name || '');
    formData.append('bank_account_number', emp.bank_account_number || '');
    if (emp.works_on_saturday === null || emp.works_on_saturday === undefined) {
      formData.append('works_on_saturday', '');
    } else {
      formData.append('works_on_saturday', emp.works_on_saturday ? '1' : '0');
    }

    // emp.roles.forEach((role, index) => formData.append(`roles[${index}]`, role));
    emp.location_id.forEach((id, index) => formData.append(`location_id[${index}]`, id.toString()));
    emp.work_type_id.forEach((id, index) => formData.append(`work_type_id[${index}]`, id.toString()));

    return this.http.post<any>(this.baseURL + "/users/update_user/" + empId, formData, { headers });
  }

  updatePassword(pass: string, empId: number) {
    const token = localStorage.getItem("token");
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);
    const body = {
      password: pass
    }
    return this.http.post<any>(this.baseURL + "/users/update_password/" + empId, body, { headers });
  }

  SearchByNameAndDeptAndSubDep(Name: string, deptId?: number | 'none' | null, subIds?: number[] | null, options?: { allDepartments?: boolean }) {
    const token = localStorage.getItem("token");
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);

    let params: any = { search: Name };

    if (deptId != null) {
      params.department_id = deptId;
    }

    if (subIds && subIds.length > 0 && deptId !== 'none') {
      params.sub_department_ids = subIds.join(',');
    }
    if (options?.allDepartments) {
      params.all_departments = true;
    }

    return this.http.get<UserModel[]>(this.baseURL + `/users/getAllUsers`, { headers, params });
  }

  getAllUsersName() {
    const token = localStorage.getItem("token");
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);
    return this.http.get<UserModel[]>(this.baseURL + `/users/users_by_name`, { headers });
  }

  DeleteById(id: number): Observable<AddEmployee> {
    const token = localStorage.getItem("token");
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);
    return this.http.delete<any>(this.baseURL + "/users/delete_user/" + id, { headers });
  }

  checkSerialNumber(empId: number) {
    const token = localStorage.getItem("token");
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);
    return this.http.get<any>(this.baseURL + `/auth/check_serial_number/${empId}`, { headers });
  }

  DeleteSerialNum(empId: number) {
    const token = localStorage.getItem("token");
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);
    return this.http.post<any>(this.baseURL + `/auth/remove_serial_number/${empId}`, {}, { headers });
  }

  getHrUserProfile(userId: number) {
    const token = localStorage.getItem('token');
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);
    return this.http.get<{ profile: HrUserProfile }>(`${this.baseURL}/hr/users/${userId}/profile`, { headers });
  }

  updateHrUserProfile(userId: number, payload: HrUserProfileUpdatePayload) {
    const token = localStorage.getItem('token');
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);
    return this.http.put<{ profile: HrUserProfile }>(`${this.baseURL}/hr/users/${userId}/profile`, payload, { headers });
  }

  resetVacationBalance(payload: any): Observable<any> {
    const token = localStorage.getItem("token");
    const headers = new HttpHeaders().set('Authorization', `Bearer ${token}`);
    return this.http.post<any>(`${this.baseURL}/users/reset-vacation-balance`, payload, { headers });
  }
}
