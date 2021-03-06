USE [LGS_UAT]
GO
/****** Object:  StoredProcedure [dbo].[spWebPreferredDeliveryMethodLiquor]    Script Date: 1/07/2022 11:53:49 AM ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

/*
+--------------------------------------------------------------------------------------------
| FUNCTION:  
| HISTORY:
| DATE			WHO      					DESCRIPTION OF CHANGE
| ---------------------------------------------------------------------------------
| 01/06/2015 	D Dennis & M Samohod		Created 
+--------------------------------------------------------------------------------------------*/

ALTER PROCEDURE [dbo].[spWebPreferredDeliveryMethodLiquor]
	@LicenceNumber int,
	@EmailAddress varchar(255) = NULL,
	@MobileNumber varchar(15) = NULL,
	@SMSEnabled bit = NULL,
	@Method varchar(5) = NULL,
	@GamingTaxEmail varchar(255) = NULL,
	@GamingTradingEmail varchar(255) = NULL,
	@LiquorEmail nvarchar(max) = NULL --achira

AS
SET NOCOUNT ON;
Declare @Date datetime
Declare @LIC_ID numeric(18,0)
BEGIN

	select @LIC_ID = LIC_ID from vwLicenceAll where LN_LicenceNumber = @LicenceNumber

	set @Date = GetDate()

		IF @EmailAddress is not NULL AND @MobileNumber is not NULL
		BEGIN
		UPDATE LicencePostalDetails set
			   [LPD_MobileNotification] = CASE WHEN @MobileNumber IS NOT NULL THEN @MobileNumber ELSE [LPD_MobileNotification] END 
			  ,[LPD_Email] = @EmailAddress
			  ,[LPD_CB_ID_PreferredDelivery] = @Method
			  ,[LPD_SendSMSNotifications]  = CASE WHEN @MobileNumber IS NOT NULL THEN @SMSEnabled ELSE [LPD_SendSMSNotifications] END  
			  ,LicencePostalDetails.LastUpdateDateTime = @Date
			  ,LicencePostalDetails.LastUpdateUser = 'SecureCBS'
		  FROM LicencePostalDetails
		  inner join Licences on LPD_ID = LIC_LPD_ID
		  inner join LicenceNumbers on LIC_LN_ID = LN_ID
		  WHERE LN_LicenceNumber = @LicenceNumber
		END


		IF @GamingTaxEmail is not NULL
		BEGIN
		INSERT INTO [dbo].[LicencePostalDetailsEmail]
				   ([LPDE_Email]
				   ,[LPDE_Consent]
				   ,[LPDE_LIC_ID]
				   ,[LPDE_DateFrom]
				   ,[LPDE_CB_ID_EmailType]
				   ,[CreationDateTime]
				   ,[CreationUser]
				   ,[LastUpdateDateTime]
				   ,[LastUpdateUser])
			 VALUES
				   (@GamingTaxEmail
				   ,1
				   ,@LIC_ID
				   ,@Date
				   ,(SELECT CB_ID from ComboBoxes where CB_Description = 'Gaming Tax Email')
				   ,@Date
				   ,'SecureCBS'
				   ,@Date
				   ,'SecureCBS')
		END

		--achira
		IF @LiquorEmail is not NULL
		BEGIN
			INSERT INTO [dbo].[LicencePostalDetailsEmail]
				   ([LPDE_Email]
				   ,[LPDE_Consent]
				   ,[LPDE_LIC_ID]
				   ,[LPDE_DateFrom]
				   ,[LPDE_CB_ID_EmailType]
				   ,[CreationDateTime]
				   ,[CreationUser]
				   ,[LastUpdateDateTime]
				   ,[LastUpdateUser])
			SELECT 
				   value,
				   1,
				   @LIC_ID,
				   @Date,
				   (SELECT CB_ID from ComboBoxes where CB_Description = 'Liquor Email'),
				   @Date,
				   'SecureCBS',
				   @Date,
				   'SecureCBS'
				   FROM OPENJSON(@LiquorEmail)
		END
		---- achira

		IF  @GamingTradingEmail is not NULL
		BEGIN
		INSERT INTO [dbo].[LicencePostalDetailsEmail]
				   ([LPDE_Email]
				   ,[LPDE_Consent]
				   ,[LPDE_LIC_ID]
				   ,[LPDE_DateFrom]
				   ,[LPDE_CB_ID_EmailType]
				   ,[CreationDateTime]
				   ,[CreationUser]
				   ,[LastUpdateDateTime]
				   ,[LastUpdateUser])
			 VALUES
				   (@GamingTradingEmail
				   ,1
				   ,@LIC_ID
				   ,@Date
				   ,(SELECT CB_ID from ComboBoxes where CB_Description = 'Gaming Trading Round Email')
				   ,@Date
				   ,'SecureCBS'
				   ,@Date
				   ,'SecureCBS')
		END   
END

