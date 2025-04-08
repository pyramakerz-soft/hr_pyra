import { ComponentFixture, TestBed } from '@angular/core/testing';

import { HrSubDepartmentAddComponent } from './hr-sub-department-add.component';

describe('HrSubDepartmentAddComponent', () => {
  let component: HrSubDepartmentAddComponent;
  let fixture: ComponentFixture<HrSubDepartmentAddComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [HrSubDepartmentAddComponent]
    })
    .compileComponents();

    fixture = TestBed.createComponent(HrSubDepartmentAddComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
